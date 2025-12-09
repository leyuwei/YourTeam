<?php
include 'auth.php';
include 'header.php';

$isManager = ($_SESSION['role'] ?? '') === 'manager';
$currentMemberId = $_SESSION['member_id'] ?? null;

$validStatuses = ['open','paused','ended','void'];

if($isManager && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';
    if($action === 'create_template'){
        $title = trim($_POST['title'] ?? '');
        $deadline = $_POST['deadline'] ?? null;
        $status = $_POST['status'] ?? 'open';
        if(!in_array($status, $validStatuses, true)) $status = 'open';
        $fieldsJson = $_POST['fields'] ?? '[]';
        $fieldsData = json_decode($fieldsJson, true) ?: [];
        $targets = $_POST['targets'] ?? [];

        if($title && $deadline){
            $stmt = $pdo->prepare("INSERT INTO collect_templates (title, deadline, status, created_by) VALUES (?,?,?,?)");
            $stmt->execute([$title, $deadline, $status, $_SESSION['username']]);
            $templateId = $pdo->lastInsertId();

            $fieldStmt = $pdo->prepare("INSERT INTO collect_fields (template_id, sort_order, label_en, label_zh, field_type, is_required, options) VALUES (?,?,?,?,?,?,?)");
            $sort = 0;
            foreach($fieldsData as $f){
                $labelEn = trim($f['label_en'] ?? '');
                $labelZh = trim($f['label_zh'] ?? '');
                $type = in_array($f['type'] ?? '', ['number','text','select','file'], true) ? $f['type'] : 'text';
                $required = !empty($f['required']) ? 1 : 0;
                $options = '';
                if($type === 'select' && !empty($f['options'])){
                    $options = implode(',', array_filter(array_map('trim', explode(',', $f['options']))));
                }
                if($labelEn || $labelZh){
                    $fieldStmt->execute([$templateId, $sort++, $labelEn, $labelZh, $type, $required, $options]);
                }
            }

            $targetStmt = $pdo->prepare("INSERT IGNORE INTO collect_template_targets (template_id, member_id, status) VALUES (?,?, 'pending')");
            foreach($targets as $memberId){
                $targetStmt->execute([$templateId, $memberId]);
            }
        }
    }

    if($action === 'update_status'){
        $templateId = (int)($_POST['template_id'] ?? 0);
        $newStatus = $_POST['status'] ?? 'open';
        if($templateId && in_array($newStatus, $validStatuses, true)){
            $stmt = $pdo->prepare("UPDATE collect_templates SET status=? WHERE id=?");
            $stmt->execute([$newStatus, $templateId]);
        }
    }

    if($action === 'toggle_target'){
        $templateId = (int)($_POST['template_id'] ?? 0);
        $memberId = (int)($_POST['member_id'] ?? 0);
        if($templateId && $memberId){
            $stmt = $pdo->prepare("SELECT status FROM collect_template_targets WHERE template_id=? AND member_id=?");
            $stmt->execute([$templateId, $memberId]);
            $current = $stmt->fetchColumn();
            if($current){
                $newStatus = $current === 'submitted' ? 'pending' : 'submitted';
                $update = $pdo->prepare("UPDATE collect_template_targets SET status=?, submitted_at = (CASE WHEN ?='submitted' THEN NOW() ELSE NULL END) WHERE template_id=? AND member_id=?");
                $update->execute([$newStatus, $newStatus, $templateId, $memberId]);
            }
        }
    }
}

$templateSql = "SELECT * FROM collect_templates";
if($isManager){
    $templateSql .= " ORDER BY FIELD(status,'open','paused','ended','void'), deadline ASC, id DESC";
    $tplStmt = $pdo->query($templateSql);
} else {
    $templateSql = "SELECT t.* FROM collect_templates t JOIN collect_template_targets tgt ON tgt.template_id=t.id WHERE tgt.member_id=? AND t.status IN ('open','paused') ORDER BY t.deadline ASC, t.id DESC";
    $tplStmt = $pdo->prepare($templateSql);
    $tplStmt->execute([$currentMemberId]);
}
$templates = $tplStmt->fetchAll(PDO::FETCH_ASSOC);

$templateIds = array_column($templates, 'id');
$fieldsByTemplate = [];
$targetsByTemplate = [];
if(!empty($templateIds)){
    $placeholders = implode(',', array_fill(0, count($templateIds), '?'));
    $fieldStmt = $pdo->prepare("SELECT * FROM collect_fields WHERE template_id IN ($placeholders) ORDER BY template_id, sort_order, id");
    $fieldStmt->execute($templateIds);
    foreach($fieldStmt->fetchAll(PDO::FETCH_ASSOC) as $f){
        $fieldsByTemplate[$f['template_id']][] = $f;
    }

    if($isManager){
        $targetStmt = $pdo->query("SELECT t.*, m.name, m.campus_id, m.department, m.status AS member_status FROM collect_template_targets t JOIN members m ON t.member_id=m.id ORDER BY m.name");
        foreach($targetStmt->fetchAll(PDO::FETCH_ASSOC) as $t){
            $targetsByTemplate[$t['template_id']][] = $t;
        }
    } else {
        $targetStmt = $pdo->prepare("SELECT t.*, m.name, m.campus_id, m.department, m.status AS member_status FROM collect_template_targets t JOIN members m ON t.member_id=m.id WHERE t.member_id=?");
        $targetStmt->execute([$currentMemberId]);
        foreach($targetStmt->fetchAll(PDO::FETCH_ASSOC) as $t){
            $targetsByTemplate[$t['template_id']][] = $t;
        }
    }
}

$members = $isManager ? $pdo->query("SELECT id, name, campus_id, department, status FROM members ORDER BY status='in_work' DESC, name ASC")->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <p class="text-muted mb-1" data-i18n="collect.subtitle">Launch structured data collection and track completion at a glance.</p>
  </div>
  <?php if($isManager): ?>
    <div class="d-flex gap-2">
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#templateModal" data-i18n="collect.add_template">New Template</button>
    </div>
  <?php endif; ?>
</div>

<?php
$activeTemplates = array_filter($templates, fn($t) => in_array($t['status'], ['open','paused']));
$archivedTemplates = array_filter($templates, fn($t) => in_array($t['status'], ['ended','void']));
?>
<div class="row g-3">
  <?php foreach($activeTemplates as $tpl): ?>
    <?php
      $tplTargets = $targetsByTemplate[$tpl['id']] ?? [];
      $submitted = array_filter($tplTargets, fn($t) => $t['status'] === 'submitted');
      $pending = array_filter($tplTargets, fn($t) => $t['status'] !== 'submitted');
      $tplFields = $fieldsByTemplate[$tpl['id']] ?? [];
    ?>
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
              <h4 class="mb-1"><?= htmlspecialchars($tpl['title']); ?></h4>
              <div class="text-muted small" data-i18n="collect.deadline_label">Deadline:</div>
              <div class="fw-semibold"><?= htmlspecialchars($tpl['deadline']); ?></div>
            </div>
            <div class="text-end">
              <span class="badge fs-6 mb-2 <?php
                if($tpl['status']==='open') echo 'bg-success';
                elseif($tpl['status']==='paused') echo 'bg-warning text-dark';
                elseif($tpl['status']==='ended') echo 'bg-secondary';
                else echo 'bg-dark';
              ?>" data-i18n="collect.status.<?= $tpl['status']; ?>"></span>
              <?php if($isManager): ?>
                <form class="mt-2" method="post">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="template_id" value="<?= $tpl['id']; ?>">
                  <div class="input-group input-group-sm">
                    <select name="status" class="form-select">
                      <?php foreach($validStatuses as $s): ?>
                        <option value="<?= $s; ?>" <?= $tpl['status']===$s?'selected':''; ?> data-i18n="collect.status.<?= $s; ?>"><?php echo htmlspecialchars($s); ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-primary" type="submit" data-i18n="collect.status.update">Update</button>
                  </div>
                </form>
              <?php endif; ?>
            </div>
          </div>
          <div class="mt-3 row">
            <div class="col-md-6">
              <div class="mb-2 fw-semibold" data-i18n="collect.fields">Fields</div>
              <ul class="list-group list-group-flush">
                <?php if(empty($tplFields)): ?>
                  <li class="list-group-item" data-i18n="collect.fields.empty">No fields configured.</li>
                <?php else: foreach($tplFields as $field): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <div class="fw-semibold"><?= htmlspecialchars($field['label_zh'] ?: $field['label_en']); ?></div>
                      <div class="text-muted small"><?= htmlspecialchars($field['label_en']); ?></div>
                    </div>
                    <div class="text-end">
                      <span class="badge bg-light text-dark" data-i18n="collect.type.<?= $field['field_type']; ?>"></span>
                      <?php if($field['is_required']): ?><span class="badge bg-danger" data-i18n="collect.required">Required</span><?php endif; ?>
                      <?php if($field['field_type']==='select' && $field['options']): ?>
                        <div class="small text-muted" data-i18n="collect.select.options" data-i18n-params='{"options":"<?= htmlspecialchars($field['options'], ENT_QUOTES); ?>"}'>Options: <?= htmlspecialchars($field['options']); ?></div>
                      <?php endif; ?>
                    </div>
                  </li>
                <?php endforeach; endif; ?>
              </ul>
            </div>
            <div class="col-md-6">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold" data-i18n="collect.targets">Target Members</div>
                <div class="small text-muted" data-i18n="collect.completion">Completion</div>
              </div>
              <div class="progress mb-2" style="height: 6px;">
                <?php
                  $totalTargets = count($tplTargets);
                  $completedCount = count($submitted);
                  $percent = $totalTargets ? intval(($completedCount/$totalTargets)*100) : 0;
                ?>
                <div class="progress-bar" role="progressbar" style="width: <?= $percent; ?>%"></div>
              </div>
              <div class="small text-muted mb-2"><?= $completedCount; ?> / <?= $totalTargets; ?> <span data-i18n="collect.completed">completed</span></div>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr>
                      <th data-i18n="collect.table.member">Member</th>
                      <th data-i18n="collect.table.department">Department</th>
                      <th data-i18n="collect.table.status">Status</th>
                      <?php if($isManager): ?>
                        <th class="text-end" data-i18n="collect.table.action">Action</th>
                      <?php endif; ?>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach($tplTargets as $tg): ?>
                      <tr>
                        <td>
                          <div class="fw-semibold"><?= htmlspecialchars($tg['name']); ?></div>
                          <div class="text-muted small">#<?= htmlspecialchars($tg['campus_id']); ?></div>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($tg['department']); ?></td>
                        <td>
                          <span class="badge <?= $tg['status']==='submitted' ? 'bg-success' : 'bg-secondary'; ?>" data-i18n="collect.member_status.<?= $tg['status']; ?>"></span>
                        </td>
                        <?php if($isManager): ?>
                          <td class="text-end">
                            <form method="post" class="d-inline">
                              <input type="hidden" name="action" value="toggle_target">
                              <input type="hidden" name="template_id" value="<?= $tpl['id']; ?>">
                              <input type="hidden" name="member_id" value="<?= $tg['member_id']; ?>">
                              <button class="btn btn-sm <?= $tg['status']==='submitted' ? 'btn-outline-secondary' : 'btn-outline-success'; ?>" type="submit" data-i18n="collect.member.toggle"></button>
                            </form>
                          </td>
                        <?php endif; ?>
                      </tr>
                    <?php endforeach; ?>
                    <?php if(empty($tplTargets)): ?>
                      <tr><td colspan="<?= $isManager ? 4 : 3; ?>" class="text-muted" data-i18n="collect.targets.empty">No target members added.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if($isManager): ?>
<div class="mt-4">
  <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#archivedBox" aria-expanded="false" aria-controls="archivedBox" data-i18n="collect.archive.toggle">Show ended/voided templates</button>
  <div class="collapse mt-3" id="archivedBox">
    <div class="row g-3">
      <?php foreach($archivedTemplates as $tpl): ?>
        <?php $tplTargets = $targetsByTemplate[$tpl['id']] ?? []; $submitted = array_filter($tplTargets, fn($t) => $t['status']==='submitted'); $pending = array_filter($tplTargets, fn($t) => $t['status']!=='submitted'); ?>
        <div class="col-12">
          <div class="card border-0 shadow-sm bg-light">
            <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div>
                <h5 class="mb-1"><?= htmlspecialchars($tpl['title']); ?></h5>
                <div class="text-muted small" data-i18n="collect.deadline_label">Deadline:</div>
                <div class="fw-semibold"><?= htmlspecialchars($tpl['deadline']); ?></div>
                <div class="small mt-2 text-muted">
                  <span class="me-2" data-i18n="collect.completed">completed</span>: <?= count($submitted); ?>
                  <span class="ms-2" data-i18n="collect.pending">pending</span>: <?= count($pending); ?>
                </div>
              </div>
              <div class="text-end">
                <span class="badge fs-6 <?= $tpl['status']==='ended' ? 'bg-secondary' : 'bg-dark'; ?>" data-i18n="collect.status.<?= $tpl['status']; ?>"></span>
                <form class="mt-2" method="post">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="template_id" value="<?= $tpl['id']; ?>">
                  <div class="input-group input-group-sm">
                    <select name="status" class="form-select">
                      <?php foreach($validStatuses as $s): ?>
                        <option value="<?= $s; ?>" <?= $tpl['status']===$s?'selected':''; ?> data-i18n="collect.status.<?= $s; ?>"><?php echo htmlspecialchars($s); ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-primary" type="submit" data-i18n="collect.status.update">Update</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if(empty($archivedTemplates)): ?>
        <div class="col-12 text-muted" data-i18n="collect.archive.empty">No ended or voided templates.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if($isManager): ?>
<div class="modal fade" id="templateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" id="templateForm">
        <input type="hidden" name="action" value="create_template">
        <input type="hidden" name="fields" id="fieldsInput">
        <div class="modal-header">
          <h5 class="modal-title" data-i18n="collect.modal.title">New Collect Template</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" data-i18n="collect.modal.name">Template Name</label>
              <input type="text" class="form-control" name="title" required>
            </div>
            <div class="col-md-3">
              <label class="form-label" data-i18n="collect.modal.deadline">Deadline</label>
              <input type="date" class="form-control" name="deadline" required>
            </div>
            <div class="col-md-3">
              <label class="form-label" data-i18n="collect.modal.status">Initial Status</label>
              <select name="status" class="form-select">
                <?php foreach($validStatuses as $s): ?>
                  <option value="<?= $s; ?>" data-i18n="collect.status.<?= $s; ?>"><?= htmlspecialchars($s); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <hr class="my-4">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <div class="fw-semibold" data-i18n="collect.modal.fields">Field Builder</div>
              <div class="text-muted small" data-i18n="collect.modal.fields_hint">Add each field with bilingual labels and choose the type.</div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="addFieldBtn" data-i18n="collect.modal.add_field">Add Field</button>
          </div>
          <div id="fieldsArea" class="d-grid gap-3"></div>

          <hr class="my-4">
          <div class="mb-2">
            <div class="fw-semibold" data-i18n="collect.modal.targets">Target Members</div>
            <div class="text-muted small" data-i18n="collect.modal.targets_hint">Select who needs to fill this template.</div>
          </div>
          <div class="mb-2">
            <input type="search" class="form-control" id="memberSearch" placeholder="Filter members" data-i18n-placeholder="collect.modal.search_members">
          </div>
          <div class="table-responsive" style="max-height: 320px;">
            <table class="table table-hover align-middle mb-0" id="memberTable">
              <thead class="table-light">
                <tr>
                  <th style="width:36px;"></th>
                  <th data-i18n="collect.table.member">Member</th>
                  <th data-i18n="collect.table.department">Department</th>
                  <th data-i18n="collect.table.status">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($members as $m): ?>
                  <tr data-name="<?= htmlspecialchars(strtolower($m['name'])); ?>" data-dept="<?= htmlspecialchars(strtolower($m['department'])); ?>">
                    <td><input type="checkbox" name="targets[]" value="<?= $m['id']; ?>" class="form-check-input"></td>
                    <td>
                      <div class="fw-semibold"><?= htmlspecialchars($m['name']); ?></div>
                      <div class="text-muted small">#<?= htmlspecialchars($m['campus_id']); ?></div>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($m['department']); ?></td>
                    <td>
                      <span class="badge <?= $m['status']==='in_work' ? 'bg-success' : 'bg-secondary'; ?>"><?= htmlspecialchars($m['status']); ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-i18n="collect.modal.cancel">Cancel</button>
          <button type="submit" class="btn btn-primary" data-i18n="collect.modal.save">Save Template</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php endif; ?>

<script>
(function(){
  const fieldsArea = document.getElementById('fieldsArea');
  const addFieldBtn = document.getElementById('addFieldBtn');
  const fieldsInput = document.getElementById('fieldsInput');
  const memberSearch = document.getElementById('memberSearch');
  const memberTable = document.getElementById('memberTable');

  function createFieldRow(){
    const wrapper = document.createElement('div');
    wrapper.className = 'card card-body shadow-sm';
    wrapper.innerHTML = `
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label" data-i18n="collect.field.label_en">Label (EN)</label>
          <input type="text" class="form-control field-label-en" placeholder="Budget" required>
        </div>
        <div class="col-md-3">
          <label class="form-label" data-i18n="collect.field.label_zh">标签 (中文)</label>
          <input type="text" class="form-control field-label-zh" placeholder="预算" required>
        </div>
        <div class="col-md-2">
          <label class="form-label" data-i18n="collect.field.type">Type</label>
          <select class="form-select field-type">
            <option value="text" data-i18n="collect.type.text">Text</option>
            <option value="number" data-i18n="collect.type.number">Number</option>
            <option value="select" data-i18n="collect.type.select">Dropdown</option>
            <option value="file" data-i18n="collect.type.file">File</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label" data-i18n="collect.field.required">Required</label>
          <select class="form-select field-required">
            <option value="1" data-i18n="collect.required">Required</option>
            <option value="0" data-i18n="collect.optional">Optional</option>
          </select>
        </div>
        <div class="col-md-2 text-end">
          <button type="button" class="btn btn-outline-danger remove-field" data-i18n="collect.field.remove">Remove</button>
        </div>
        <div class="col-12 options-area mt-2" style="display:none;">
          <label class="form-label" data-i18n="collect.field.options">Options (comma separated)</label>
          <input type="text" class="form-control field-options" placeholder="Option A, Option B">
        </div>
      </div>
    `;

    wrapper.querySelector('.field-type').addEventListener('change', (e)=>{
      const show = e.target.value === 'select';
      wrapper.querySelector('.options-area').style.display = show ? 'block' : 'none';
    });
    wrapper.querySelector('.remove-field').addEventListener('click', ()=> wrapper.remove());
    return wrapper;
  }

  addFieldBtn?.addEventListener('click', ()=>{
    fieldsArea.appendChild(createFieldRow());
  });

  document.getElementById('templateForm')?.addEventListener('submit', (e)=>{
    const fieldRows = Array.from(fieldsArea.querySelectorAll('.card'));
    const payload = fieldRows.map(row => ({
      label_en: row.querySelector('.field-label-en')?.value || '',
      label_zh: row.querySelector('.field-label-zh')?.value || '',
      type: row.querySelector('.field-type')?.value || 'text',
      required: row.querySelector('.field-required')?.value === '1',
      options: row.querySelector('.field-options')?.value || ''
    })).filter(f => f.label_en || f.label_zh);
    fieldsInput.value = JSON.stringify(payload);
  });

  memberSearch?.addEventListener('input', (e)=>{
    const keyword = e.target.value.toLowerCase();
    memberTable.querySelectorAll('tbody tr').forEach(row => {
      const name = row.dataset.name || '';
      const dept = row.dataset.dept || '';
      row.style.display = (name.includes(keyword) || dept.includes(keyword)) ? '' : 'none';
    });
  });
})();
</script>
<?php include 'footer.php'; ?>
