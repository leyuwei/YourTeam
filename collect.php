<?php
include 'auth.php';

$is_manager = ($_SESSION['role'] === 'manager');
$member_id = $_SESSION['member_id'] ?? null;

function decode_json_or($value, $fallback = []) {
    if (!$value) return $fallback;
    $data = json_decode($value, true);
    return $data ?: $fallback;
}

function ensure_collect_upload_dir($templateId) {
    $dir = __DIR__ . '/collect_uploads/' . intval($templateId);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

function clean_collect_files($templateId) {
    $base = __DIR__ . '/collect_uploads/' . intval($templateId);
    if (is_dir($base)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($base);
    }
}

function delete_submission_files($templateId, $data) {
    foreach ($data as $val) {
        if (($val['type'] ?? '') === 'file' && isset($val['value']['stored'])) {
            $filePath = __DIR__ . '/collect_uploads/' . intval($templateId) . '/' . $val['value']['stored'];
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }
    }
}

if ($is_manager && isset($_GET['download'])) {
    $templateId = intval($_GET['download']);
    $stmt = $pdo->prepare("SELECT * FROM collect_templates WHERE id=?");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($template) {
        $fields = decode_json_or($template['fields_json']);
        $fieldOrder = array_map(fn($f) => $f['id'], $fields);
        $fieldLabels = [];
        foreach ($fields as $f) {
            $fieldLabels[$f['id']] = $f['label'];
        }
        $subStmt = $pdo->prepare("SELECT cs.*, m.name FROM collect_submissions cs LEFT JOIN members m ON cs.member_id=m.id WHERE cs.template_id=? ORDER BY cs.created_at DESC");
        $subStmt->execute([$templateId]);
        $subs = $subStmt->fetchAll(PDO::FETCH_ASSOC);

        $zip = new ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'collect');
        $zip->open($tmp, ZipArchive::OVERWRITE);

        $csvRows = [];
        $header = ['Member'];
        foreach ($fieldOrder as $fid) {
            $header[] = $fieldLabels[$fid] ?? $fid;
        }
        $header[] = 'Created At';
        $header[] = 'Updated At';
        $csvRows[] = $header;

        foreach ($subs as $row) {
            $data = decode_json_or($row['data_json']);
            $csvRow = [$row['name']];
            foreach ($fieldOrder as $fid) {
                $value = $data[$fid]['value'] ?? '';
                $csvRow[] = is_array($value) ? ($value['original'] ?? '') : $value;
            }
            $csvRow[] = $row['created_at'];
            $csvRow[] = $row['updated_at'];
            $csvRows[] = $csvRow;

            foreach ($data as $fid => $val) {
                if (($val['type'] ?? '') === 'file' && isset($val['value']['stored'])) {
                    $filePath = __DIR__ . '/collect_uploads/' . $templateId . '/' . $val['value']['stored'];
                    if (is_file($filePath)) {
                        $zip->addFile($filePath, 'files/' . ($row['name'] ?: 'member') . '_submission' . $row['id'] . '/' . ($val['value']['original'] ?? basename($filePath)));
                    }
                }
            }
        }

        $csvContent = '';
        foreach ($csvRows as $csvRow) {
            $csvContent .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $csvRow)) . "\n";
        }
        $zip->addFromString('submissions.csv', $csvContent);
        $zip->close();

        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="collect_' . $templateId . '.zip"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }
}

if ($is_manager && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_template') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'open';
    $deadline = $_POST['deadline'] ?: null;
    $fieldsJson = $_POST['fields_json'] ?? '[]';
    $targets = $_POST['targets'] ?? [];
    $targetJson = json_encode(array_map('intval', $targets));
    $now = date('Y-m-d H:i:s');
    if ($id) {
        $stmt = $pdo->prepare("UPDATE collect_templates SET name=?, description=?, status=?, deadline=?, fields_json=?, target_member_ids=?, updated_at=? WHERE id=?");
        $stmt->execute([$name, $description, $status, $deadline, $fieldsJson, $targetJson, $now, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO collect_templates (name, description, status, deadline, fields_json, target_member_ids, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$name, $description, $status, $deadline, $fieldsJson, $targetJson, $now, $now]);
    }
    header('Location: collect.php');
    exit;
}

if ($is_manager && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_template') {
    $id = intval($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM collect_submissions WHERE template_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM collect_templates WHERE id=?")->execute([$id]);
    clean_collect_files($id);
    header('Location: collect.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_submission') {
    $templateId = intval($_POST['template_id']);
    $submissionId = intval($_POST['submission_id'] ?? 0);
    $toast = 'record_failed';
    $stmt = $pdo->prepare("SELECT * FROM collect_templates WHERE id=?");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($template) {
        $targets = decode_json_or($template['target_member_ids']);
        if (!$is_manager && (!in_array($member_id, $targets) || $template['status'] !== 'open')) {
            header('Location: collect.php?denied=1');
            exit;
        }
        $fields = decode_json_or($template['fields_json']);
        $data = [];
        $uploadDir = ensure_collect_upload_dir($templateId);
        foreach ($fields as $f) {
            $fid = $f['id'];
            $value = $_POST['field'][$fid] ?? '';
            if ($f['type'] === 'file') {
                $existing = [];
                if ($submissionId) {
                    $oldStmt = $pdo->prepare("SELECT data_json FROM collect_submissions WHERE id=?");
                    $oldStmt->execute([$submissionId]);
                    $oldData = decode_json_or($oldStmt->fetchColumn());
                    $existing = $oldData[$fid]['value'] ?? [];
                }
                if (isset($_FILES['field']['name'][$fid]) && $_FILES['field']['tmp_name'][$fid]) {
                    $original = $_FILES['field']['name'][$fid];
                    $stored = uniqid('f_', true) . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $original);
                    move_uploaded_file($_FILES['field']['tmp_name'][$fid], $uploadDir . '/' . $stored);
                    $value = ['original' => $original, 'stored' => $stored];
                } else {
                    $value = $existing;
                }
            }
            $data[$fid] = [
                'label' => $f['label'],
                'type' => $f['type'],
                'value' => $value,
                'required' => !empty($f['required'])
            ];
        }
        $now = date('Y-m-d H:i:s');
        if ($submissionId) {
            $stmt = $pdo->prepare("UPDATE collect_submissions SET data_json=?, updated_at=? WHERE id=? AND member_id=?");
            $stmt->execute([json_encode($data), $now, $submissionId, $member_id]);
            $toast = 'record_updated';
        } else {
            $stmt = $pdo->prepare("INSERT INTO collect_submissions (template_id, member_id, data_json, created_at, updated_at) VALUES (?,?,?,?,?)");
            $stmt->execute([$templateId, $member_id, json_encode($data), $now, $now]);
            $toast = 'record_created';
        }
    }
    header('Location: collect.php?toast=' . $toast);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_submission') {
    $templateId = intval($_POST['template_id']);
    $submissionId = intval($_POST['submission_id'] ?? 0);
    $toast = 'record_failed';
    $subStmt = $pdo->prepare("SELECT * FROM collect_submissions WHERE id=? AND template_id=?");
    $subStmt->execute([$submissionId, $templateId]);
    $submission = $subStmt->fetch(PDO::FETCH_ASSOC);
    if ($submission) {
        $tplStmt = $pdo->prepare("SELECT status, target_member_ids FROM collect_templates WHERE id=?");
        $tplStmt->execute([$templateId]);
        $template = $tplStmt->fetch(PDO::FETCH_ASSOC);
        $targets = decode_json_or($template['target_member_ids'] ?? '[]');
        $canDelete = $is_manager || ($template && $template['status'] === 'open' && $submission['member_id'] == $member_id && (empty($targets) || in_array($member_id, $targets)));
        if ($canDelete) {
            $data = decode_json_or($submission['data_json']);
            delete_submission_files($templateId, $data);
            $pdo->prepare("DELETE FROM collect_submissions WHERE id=?")->execute([$submissionId]);
            $toast = 'record_deleted';
        }
    }
    header('Location: collect.php?toast=' . $toast);
    exit;
}

$templates = $pdo->query("SELECT * FROM collect_templates ORDER BY (status IN ('ended','void')), COALESCE(deadline,'9999-12-31') ASC, updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$members = $pdo->query("SELECT id,name,status,department FROM members ORDER BY status='in_work' DESC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

$templateSubmissions = [];
$submissionStmt = $pdo->query("SELECT cs.*, m.name FROM collect_submissions cs LEFT JOIN members m ON cs.member_id=m.id");
foreach ($submissionStmt as $sub) {
    $templateSubmissions[$sub['template_id']][] = $sub;
}

include 'header.php';
?>
<style>
  .collect-card { border:1px solid var(--app-surface-border); box-shadow:var(--app-card-shadow); }
  .collect-status { font-size:0.9rem; }
  .target-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:0.5rem; max-height:280px; overflow:auto; padding:0.25rem; background:var(--app-table-striped-bg); border-radius:0.5rem; }
  .target-card { border:1px solid var(--app-table-border); border-radius:0.5rem; padding:0.5rem 0.6rem; background:var(--app-surface-bg); box-shadow:0 2px 6px rgba(0,0,0,0.04); }
  .target-card input { margin-right:0.35rem; }
  .collect-field-row { border:1px dashed var(--app-table-border); padding:0.75rem; border-radius:0.5rem; background:var(--app-table-striped-bg); }
  .collect-field-row + .collect-field-row { margin-top:0.5rem; }
  .archived-section { display:none; }
  .collect-badge { padding:0.25rem 0.5rem; border-radius:0.5rem; }
  .member-list-body { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:0.5rem; }
  .member-chip { border:1px solid var(--app-table-border); border-radius:0.45rem; padding:0.35rem 0.5rem; background:var(--app-surface-bg); display:flex; justify-content:space-between; align-items:center; gap:0.35rem; box-shadow:0 1px 4px rgba(0,0,0,0.03); }
  .member-chip .meta { color:var(--bs-gray-600); font-size:0.85rem; }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="mb-0" data-i18n="collect.title">Collect</h2>
  <?php if($is_manager): ?>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal" data-mode="add" data-i18n="collect.add_template">New Form</button>
  <?php endif; ?>
</div>
<div class="d-flex align-items-center mb-3">
  <button class="btn btn-outline-secondary" id="toggleArchived" data-i18n="collect.show_archived">Show ended/void forms</button>
</div>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1080;">
  <div id="collectToast" class="toast align-items-center text-bg-primary" role="status" aria-live="polite" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<?php
$archived = [];
$active = [];
foreach ($templates as $t) {
    if (in_array($t['status'], ['ended','void'])) {
        $archived[] = $t;
    } else {
        $active[] = $t;
    }
}
function render_collect_card($t, $is_manager, $member_id, $members, $templateSubmissions) {
    $targets = decode_json_or($t['target_member_ids']);
    $fields = decode_json_or($t['fields_json']);
    $subs = $templateSubmissions[$t['id']] ?? [];
    $submittedMemberIds = array_unique(array_column($subs, 'member_id'));
    $remaining = array_diff($targets, $submittedMemberIds);
    $assignedCount = count($targets);
    $statusLabel = $t['status'];
    ?>
    <div class="card mb-3 collect-card" data-template-id="<?= $t['id']; ?>">
      <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div>
            <h4 class="mb-1"><?= htmlspecialchars($t['name']); ?></h4>
            <div class="text-muted small" data-i18n="collect.template_card.status_label">Form status</div>
            <span class="badge bg-info collect-status" data-i18n="collect.status.<?= $statusLabel; ?>"><?= htmlspecialchars($statusLabel); ?></span>
            <?php if($t['deadline']): ?><span class="ms-2 text-muted small"><?= htmlspecialchars($t['deadline']); ?></span><?php endif; ?>
          </div>
          <div class="text-end">
            <div class="small" data-i18n="collect.template_card.assignees">Assignees</div>
            <div class="fs-5"><?= $assignedCount; ?></div>
            <div class="small" data-i18n="collect.template_card.submissions">Submissions</div>
            <div class="fs-6"><?= count($submittedMemberIds); ?></div>
          </div>
        </div>
        <?php if(!empty($t['description'])): ?>
        <p class="mt-2 mb-3 text-muted"><?= nl2br(htmlspecialchars($t['description'])); ?></p>
        <?php endif; ?>
        <div class="d-flex flex-wrap gap-2 mb-3">
          <?php if($is_manager): ?>
            <button class="btn btn-sm btn-secondary edit-template" data-bs-toggle="modal" data-bs-target="#templateModal" data-mode="edit" data-template='<?= htmlspecialchars(json_encode($t, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES); ?>' data-i18n="collect.template_card.edit">Edit</button>
            <form method="post" class="d-inline" onsubmit="return doubleConfirm(translations[document.documentElement.lang||'zh']['collect.confirm_delete']);">
              <input type="hidden" name="action" value="delete_template">
              <input type="hidden" name="id" value="<?= $t['id']; ?>">
              <button class="btn btn-sm btn-danger" data-i18n="collect.template_card.delete">Delete</button>
            </form>
            <a class="btn btn-sm btn-outline-primary" href="collect.php?download=<?= $t['id']; ?>" data-i18n="collect.download_zip">Download ZIP</a>
          <?php endif; ?>
          <?php if($t['status']==='open' && (empty($targets) || in_array($member_id,$targets))): ?>
            <a class="btn btn-sm btn-success" href="#fill-<?= $t['id']; ?>" data-i18n="collect.template_card.fill">Fill Form</a>
          <?php else: ?>
            <span class="text-muted small" data-i18n="collect.template_card.status_hint">Only open forms can be filled.</span>
          <?php endif; ?>
        </div>
        <?php if($is_manager): ?>
        <?php
          $submittedMembers = [];
          foreach($members as $m){
            if(in_array($m['id'],$submittedMemberIds)){
              $submittedMembers[] = ['name'=>$m['name'],'department'=>$m['department']];
            }
          }
          $pendingMembers = [];
          foreach($members as $m){
            if(in_array($m['id'],$remaining)){
              $pendingMembers[] = ['name'=>$m['name'],'department'=>$m['department']];
            }
          }
        ?>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <h6 class="mb-2" data-i18n="collect.template_card.members_done">Submitted</h6>
            <div class="member-list-panel" data-members='<?= htmlspecialchars(json_encode($submittedMembers, JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>' data-size="8">
              <div class="member-list-body"></div>
              <div class="d-flex justify-content-between align-items-center mt-2 member-list-controls">
                <div class="small text-muted member-page-indicator"></div>
                <div class="btn-group btn-group-sm" role="group">
                  <button type="button" class="btn btn-outline-secondary member-page-prev">‹</button>
                  <button type="button" class="btn btn-outline-secondary member-page-next">›</button>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <h6 class="mb-2" data-i18n="collect.template_card.members_pending">Not submitted</h6>
            <div class="member-list-panel" data-members='<?= htmlspecialchars(json_encode($pendingMembers, JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>' data-size="8">
              <div class="member-list-body"></div>
              <div class="d-flex justify-content-between align-items-center mt-2 member-list-controls">
                <div class="small text-muted member-page-indicator"></div>
                <div class="btn-group btn-group-sm" role="group">
                  <button type="button" class="btn btn-outline-secondary member-page-prev">‹</button>
                  <button type="button" class="btn btn-outline-secondary member-page-next">›</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <div id="fill-<?= $t['id']; ?>">
          <h5 class="mt-3" data-i18n="collect.template_card.records">My Records</h5>
          <?php if($t['status'] !== 'open' || (!empty($targets) && !in_array($member_id,$targets))): ?>
            <div class="alert alert-light" data-i18n="collect.access_denied">Only open forms assigned to you can be filled.</div>
          <?php else: ?>
            <?php
              $mySubs = array_filter($subs, fn($s) => $s['member_id']==$member_id);
            ?>
            <?php if(!$mySubs): ?>
              <p class="text-muted" data-i18n="collect.template_card.no_records">No records yet.</p>
            <?php endif; ?>
            <?php foreach($mySubs as $sub): $subData = decode_json_or($sub['data_json']); ?>
              <form class="border rounded p-3 mb-3" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_submission" class="collect-action-input">
                <input type="hidden" name="template_id" value="<?= $t['id']; ?>">
                <input type="hidden" name="submission_id" value="<?= $sub['id']; ?>">
                <div class="row g-3">
                <?php foreach($fields as $field): $fid=$field['id']; $val=$subData[$fid]['value'] ?? ''; ?>
                  <div class="col-md-6">
                    <label class="form-label"><?= htmlspecialchars($field['label']); ?><?php if(!empty($field['required'])) echo ' *'; ?></label>
                    <?php if($field['type']==='text'): ?>
                      <input type="text" class="form-control" name="field[<?= $fid; ?>]" value="<?= htmlspecialchars(is_array($val)?'':$val); ?>" <?= !empty($field['required']) ? 'required' : ''; ?>>
                    <?php elseif($field['type']==='number'): ?>
                      <input type="number" class="form-control" name="field[<?= $fid; ?>]" value="<?= htmlspecialchars(is_array($val)?'':$val); ?>" <?= !empty($field['required']) ? 'required' : ''; ?>>
                    <?php elseif($field['type']==='select'): $opts = array_filter(array_map('trim',$field['options']??[])); ?>
                      <select class="form-select" name="field[<?= $fid; ?>]" <?= !empty($field['required']) ? 'required' : ''; ?>>
                        <option value="">-</option>
                        <?php foreach($opts as $opt): ?>
                          <option value="<?= htmlspecialchars($opt); ?>" <?= ($val==$opt)?'selected':''; ?>><?= htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                      </select>
                    <?php elseif($field['type']==='file'): ?>
                      <?php if(is_array($val) && !empty($val['stored'])): ?>
                        <div class="form-text" data-i18n="collect.file_current">Current file</div>
                        <a href="collect_uploads/<?= $t['id']; ?>/<?= urlencode($val['stored']); ?>" target="_blank"><?= htmlspecialchars($val['original'] ?? $val['stored']); ?></a>
                      <?php endif; ?>
                      <div class="form-text" data-i18n="collect.file_replace">Upload to replace</div>
                      <input type="file" class="form-control" name="field[<?= $fid; ?>]" <?= !empty($field['required']) && empty($val) ? 'required' : ''; ?>>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
                </div>
                <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <button class="btn btn-primary" data-i18n="collect.update_record" onclick="this.form.querySelector('.collect-action-input').value='save_submission';">Update</button>
                  <button type="submit" class="btn btn-outline-danger" formnovalidate onclick="this.form.querySelector('.collect-action-input').value='delete_submission'; return doubleConfirm(translations[document.documentElement.lang||'zh']['collect.confirm_delete_record']);" data-i18n="collect.delete_record">Delete</button>
                </div>
              </form>
            <?php endforeach; ?>
            <div class="border rounded p-3">
              <h6 class="mb-3" data-i18n="collect.template_card.new_record">Add Record</h6>
              <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_submission">
                <input type="hidden" name="template_id" value="<?= $t['id']; ?>">
                <div class="row g-3">
                <?php foreach($fields as $field): $fid=$field['id']; ?>
                  <div class="col-md-6">
                    <label class="form-label"><?= htmlspecialchars($field['label']); ?><?php if(!empty($field['required'])) echo ' *'; ?></label>
                    <?php if($field['type']==='text'): ?>
                      <input type="text" class="form-control" name="field[<?= $fid; ?>]" <?= !empty($field['required']) ? 'required' : ''; ?>>
                    <?php elseif($field['type']==='number'): ?>
                      <input type="number" class="form-control" name="field[<?= $fid; ?>]" <?= !empty($field['required']) ? 'required' : ''; ?>>
                    <?php elseif($field['type']==='select'): $opts = array_filter(array_map('trim',$field['options']??[])); ?>
                      <select class="form-select" name="field[<?= $fid; ?>]" <?= !empty($field['required']) ? 'required' : ''; ?>>
                        <option value="">-</option>
                        <?php foreach($opts as $opt): ?>
                          <option value="<?= htmlspecialchars($opt); ?>"><?= htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                      </select>
                    <?php elseif($field['type']==='file'): ?>
                      <input type="file" class="form-control" name="field[<?= $fid; ?>]" <?= !empty($field['required']) ? 'required' : ''; ?>>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
                </div>
                <div class="mt-3 text-end">
                  <button class="btn btn-success" data-i18n="collect.submit_record">Submit</button>
                </div>
              </form>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php
}
?>
<div class="active-section">
  <?php if(!$active): ?><div class="alert alert-light" data-i18n="collect.none">None</div><?php endif; ?>
  <?php foreach($active as $t){ render_collect_card($t,$is_manager,$member_id,$members,$templateSubmissions); } ?>
</div>
<div class="archived-section mt-4">
  <h5 class="mb-3" data-i18n="collect.hide_archived">Ended/void forms</h5>
  <?php if(!$archived): ?><div class="alert alert-light" data-i18n="collect.none">None</div><?php endif; ?>
  <?php foreach($archived as $t){ render_collect_card($t,$is_manager,$member_id,$members,$templateSubmissions); } ?>
</div>

<?php if($is_manager): ?>
<div class="modal fade" id="templateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" id="templateForm">
        <input type="hidden" name="action" value="save_template">
        <input type="hidden" name="id" id="templateId">
        <input type="hidden" name="fields_json" id="fieldsJson">
        <div class="modal-header">
          <h5 class="modal-title" data-i18n="collect.add_template">New Form</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" data-i18n="collect.name">Form Name</label>
            <input type="text" class="form-control" name="name" id="templateName" required>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="collect.description">Description</label>
            <textarea class="form-control" name="description" id="templateDescription" rows="2"></textarea>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label" data-i18n="collect.status">Status</label>
              <select class="form-select" name="status" id="templateStatus">
                <option value="open" data-i18n="collect.status.open">Open</option>
                <option value="paused" data-i18n="collect.status.paused">Paused</option>
                <option value="ended" data-i18n="collect.status.ended">Ended</option>
                <option value="void" data-i18n="collect.status.void">Voided</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label" data-i18n="collect.deadline">Deadline</label>
              <input type="date" class="form-control" name="deadline" id="templateDeadline">
            </div>
          </div>
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                <label class="form-label" data-i18n="collect.targets">Target Members</label>
                <div class="text-muted small" data-i18n="collect.member_selector.subtitle">Pick the members who need to fill this form.</div>
              </div>
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary" id="targetSelectAll" data-i18n="collect.targets_select_all">Select All</button>
                <button type="button" class="btn btn-outline-secondary" id="targetInvert" data-i18n="collect.targets_invert">Invert</button>
              </div>
            </div>
            <div class="target-grid">
              <?php foreach($members as $m): if($m['status']!=='in_work') continue; ?>
                <label class="target-card">
                  <input type="checkbox" name="targets[]" value="<?= $m['id']; ?>"> <?= htmlspecialchars($m['name']); ?>
                  <?php if(!empty($m['department'])): ?><div class="text-muted small"><?= htmlspecialchars($m['department']); ?></div><?php endif; ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="mb-2 d-flex justify-content-between align-items-center">
            <label class="form-label mb-0" data-i18n="collect.fields">Fields</label>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addFieldBtn" data-i18n="collect.field_add">Add Field</button>
          </div>
          <div id="fieldsContainer"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="collect.cancel">Cancel</button>
          <button type="submit" class="btn btn-primary" data-i18n="collect.save">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
const fieldsContainer = document.getElementById('fieldsContainer');
const addFieldBtn = document.getElementById('addFieldBtn');
const fieldsJson = document.getElementById('fieldsJson');
const templateModal = document.getElementById('templateModal');
const templateForm = document.getElementById('templateForm');
const toggleArchivedBtn = document.getElementById('toggleArchived');
const archivedSection = document.querySelector('.archived-section');
const targetSelectAllBtn = document.getElementById('targetSelectAll');
const targetInvertBtn = document.getElementById('targetInvert');

function createFieldRow(field){
  const wrapper = document.createElement('div');
  wrapper.className = 'collect-field-row';
  wrapper.dataset.id = field.id;
  wrapper.innerHTML = `
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label" data-i18n="collect.field_label">Label</label>
        <input type="text" class="form-control" name="field_label" value="${field.label || ''}" required>
      </div>
      <div class="col-md-3">
        <label class="form-label" data-i18n="collect.field_type">Type</label>
        <select class="form-select" name="field_type">
          <option value="text" ${field.type==='text'?'selected':''} data-i18n="collect.field_types.text">Text</option>
          <option value="number" ${field.type==='number'?'selected':''} data-i18n="collect.field_types.number">Number</option>
          <option value="select" ${field.type==='select'?'selected':''} data-i18n="collect.field_types.select">Dropdown</option>
          <option value="file" ${field.type==='file'?'selected':''} data-i18n="collect.field_types.file">File</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" data-i18n="collect.field_options">Options</label>
        <input type="text" class="form-control" name="field_options" value="${(field.options||[]).join(', ')}" placeholder=", ">
      </div>
      <div class="col-md-1 text-center">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="field_required" ${field.required?'checked':''}>
          <label class="form-check-label" data-i18n="collect.field_required">Required</label>
        </div>
      </div>
      <div class="col-md-1 text-end">
        <button type="button" class="btn btn-sm btn-outline-danger remove-field">×</button>
      </div>
    </div>`;
  wrapper.querySelector('.remove-field').addEventListener('click', ()=>wrapper.remove());
  fieldsContainer.appendChild(wrapper);
}

function collectFields(){
  const data = [];
  fieldsContainer.querySelectorAll('.collect-field-row').forEach(row => {
    const label = row.querySelector('input[name="field_label"]').value.trim();
    const type = row.querySelector('select[name="field_type"]').value;
    const options = row.querySelector('input[name="field_options"]').value.split(',').map(v=>v.trim()).filter(Boolean);
    const required = row.querySelector('input[name="field_required"]').checked;
    data.push({ id: row.dataset.id, label, type, options, required });
  });
  fieldsJson.value = JSON.stringify(data);
}

if(addFieldBtn){
  addFieldBtn.addEventListener('click', ()=>{
    createFieldRow({id: 'f'+Date.now(), label:'', type:'text', options:[], required:false});
  });
}

if(templateForm){
  templateForm.addEventListener('submit', (e)=>{
    collectFields();
  });
}

function selectTargets(mode){
  const boxes = document.querySelectorAll('input[name="targets[]"]');
  if(!boxes.length) return;
  boxes.forEach(cb => {
    if(mode==='all') cb.checked = true;
    if(mode==='invert') cb.checked = !cb.checked;
  });
}

targetSelectAllBtn?.addEventListener('click', ()=>selectTargets('all'));
targetInvertBtn?.addEventListener('click', ()=>selectTargets('invert'));

if(templateModal){
  templateModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    fieldsContainer.innerHTML='';
    document.querySelectorAll('input[name="targets[]"]').forEach(cb=>cb.checked=false);
    if(button?.dataset.mode==='add'){
      templateForm.reset();
      document.getElementById('templateId').value='';
      document.querySelector('#templateModal .modal-title').setAttribute('data-i18n','collect.add_template');
    }
    if(button?.classList.contains('edit-template')){
      const data = JSON.parse(button.dataset.template);
      document.getElementById('templateId').value = data.id;
      document.getElementById('templateName').value = data.name;
      document.getElementById('templateDescription').value = data.description || '';
      document.getElementById('templateStatus').value = data.status;
      document.getElementById('templateDeadline').value = data.deadline || '';
      const targets = JSON.parse(data.target_member_ids || '[]');
      document.querySelectorAll('input[name="targets[]"]').forEach(cb=>cb.checked = targets.includes(parseInt(cb.value)));
      const fields = JSON.parse(data.fields_json || '[]');
      fields.forEach(f=>createFieldRow(f));
      document.querySelector('#templateModal .modal-title').setAttribute('data-i18n','collect.edit_template');
    }
    if(window.applyTranslations) applyTranslations();
  });
}

if(toggleArchivedBtn){
  toggleArchivedBtn.addEventListener('click', ()=>{
    const visible = archivedSection.style.display==='block';
    archivedSection.style.display = visible ? 'none' : 'block';
    toggleArchivedBtn.setAttribute('data-i18n', visible ? 'collect.show_archived' : 'collect.hide_archived');
    if(window.applyTranslations) applyTranslations();
  });
}

function renderMemberPanels(){
  const lang = document.documentElement.lang || 'zh';
  const noneLabel = translations[lang]?.['collect.none'] || 'None';
  const pageTemplate = translations[lang]?.['collect.member_page_info'] || 'Page {current}/{total}';
  document.querySelectorAll('.member-list-panel').forEach(panel => {
    const members = JSON.parse(panel.dataset.members || '[]');
    const size = parseInt(panel.dataset.size || '8');
    const body = panel.querySelector('.member-list-body');
    const indicator = panel.querySelector('.member-page-indicator');
    const prev = panel.querySelector('.member-page-prev');
    const next = panel.querySelector('.member-page-next');
    let page = 0;
    const renderPage = () => {
      body.innerHTML = '';
      const totalPages = Math.max(1, Math.ceil(members.length / size));
      page = Math.min(Math.max(page, 0), totalPages - 1);
      const start = page * size;
      const items = members.slice(start, start + size);
      if(items.length === 0){
        const empty = document.createElement('div');
        empty.className = 'text-muted small';
        empty.textContent = noneLabel;
        body.appendChild(empty);
      } else {
        items.forEach(m => {
          const chip = document.createElement('div');
          chip.className = 'member-chip';
          chip.innerHTML = `<span>${m.name || ''}</span>${m.department ? `<span class="meta">${m.department}</span>` : ''}`;
          body.appendChild(chip);
        });
      }
      if(indicator){
        indicator.textContent = pageTemplate.replace('{current}', page + 1).replace('{total}', totalPages);
      }
      if(prev && next){
        prev.disabled = page <= 0;
        next.disabled = page >= totalPages - 1;
        const controls = prev.closest('.member-list-controls');
        if(controls){
          controls.style.display = totalPages > 1 ? 'flex' : 'none';
        }
      }
    };
    prev?.addEventListener('click', ()=>{ page--; renderPage(); });
    next?.addEventListener('click', ()=>{ page++; renderPage(); });
    renderPage();
  });
}

renderMemberPanels();

const toastParam = new URLSearchParams(window.location.search).get('toast');
if (toastParam) {
  const toastEl = document.getElementById('collectToast');
  if (toastEl) {
    const bodyEl = toastEl.querySelector('.toast-body');
    const lang = document.documentElement.lang || 'zh';
    const keyMap = {
      record_created: 'collect.record_created',
      record_updated: 'collect.record_updated',
      record_deleted: 'collect.record_deleted',
      record_failed: 'collect.record_failed'
    };
    const closeBtn = toastEl.querySelector('[aria-label="Close"]');
    if (closeBtn && translations[lang]?.['collect.toast_close']) {
      closeBtn.setAttribute('aria-label', translations[lang]['collect.toast_close']);
    }
    const key = keyMap[toastParam];
    bodyEl.textContent = (translations[lang] && translations[lang][key]) ? translations[lang][key] : toastParam;
    if(window.applyTranslations) applyTranslations();
    const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
    toast.show();
    const url = new URL(window.location);
    url.searchParams.delete('toast');
    window.history.replaceState({}, '', url);
  }
}
</script>
<?php include 'footer.php'; ?>
