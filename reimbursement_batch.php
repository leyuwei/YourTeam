<?php
include 'auth.php';
include 'reimbursement_log.php';
include 'header.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, m.name AS in_charge_name FROM reimbursement_batches b LEFT JOIN members m ON b.in_charge_member_id=m.id WHERE b.id=?");
$stmt->execute([$id]);
$batch = $stmt->fetch();
if(!$batch){
    echo '<div class="alert alert-danger">Batch not found</div>';
    include 'footer.php';
    exit;
}
$allowedTypes = $batch['allowed_types'] ? explode(',', $batch['allowed_types']) : ['office','electronic','membership','book','trip'];
$is_manager = ($_SESSION['role'] === 'manager');
$member_id = $_SESSION['member_id'] ?? null;
$can_edit_notice = ($is_manager || $batch['in_charge_member_id']==$member_id);
$deadline_passed = (strtotime($batch['deadline']) < strtotime(date('Y-m-d')));
$batch_locked = ($batch['status'] !== 'open');
$error = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['update_notice']) && $can_edit_notice){
        $notice_en = trim($_POST['notice_en'] ?? '');
        $notice_zh = trim($_POST['notice_zh'] ?? '');
        $pdo->prepare("UPDATE reimbursement_batches SET notice_en=?, notice_zh=? WHERE id=?")->execute([$notice_en, $notice_zh, $id]);
        $batch['notice_en'] = $notice_en;
        $batch['notice_zh'] = $notice_zh;
        add_batch_log($pdo,$id,$_SESSION['username'],'Batch notice updated');
    }
    if(isset($_POST['lock']) && ($is_manager || $batch['in_charge_member_id']==$member_id)){
        $pdo->prepare("UPDATE reimbursement_batches SET status='locked' WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE reimbursement_receipts SET status='locked' WHERE batch_id=? AND status<>'refused'")->execute([$id]);
        $batch['status']='locked';
        $batch_locked = true;
        add_batch_log($pdo,$id,$_SESSION['username'],'Batch locked');
    } elseif(isset($_POST['complete']) && ($is_manager || $batch['in_charge_member_id']==$member_id)){
        $pdo->prepare("UPDATE reimbursement_batches SET status='completed' WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE reimbursement_receipts SET status='complete' WHERE batch_id=? AND status<>'refused'")->execute([$id]);
        $batch['status']='completed';
        $batch_locked = true;
        add_batch_log($pdo,$id,$_SESSION['username'],'Batch completed');
    } elseif(isset($_POST['unlock']) && ($is_manager || $batch['in_charge_member_id']==$member_id)){
        $pdo->prepare("UPDATE reimbursement_batches SET status='open' WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE reimbursement_receipts SET status='submitted' WHERE batch_id=? AND status<>'refused'")->execute([$id]);
        $batch['status']='open';
        $batch_locked = false;
        add_batch_log($pdo,$id,$_SESSION['username'],'Batch unlocked');
    } elseif(isset($_POST['reopen']) && ($is_manager || $batch['in_charge_member_id']==$member_id)){
        $pdo->prepare("UPDATE reimbursement_batches SET status='locked' WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE reimbursement_receipts SET status='locked' WHERE batch_id=? AND status<>'refused'")->execute([$id]);
        $batch['status']='locked';
        $batch_locked = true;
        add_batch_log($pdo,$id,$_SESSION['username'],'Batch reopened');
    } elseif(!$is_manager && isset($_FILES['receipt']) && $_FILES['receipt']['error']===UPLOAD_ERR_OK && !$batch_locked && !$deadline_passed){
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'] !== '' ? (float)$_POST['price'] : 0;
        if($description===''){
            $error = 'desc';
        } else {
            $totalStmt = $pdo->prepare("SELECT COALESCE(SUM(price),0) FROM reimbursement_receipts WHERE batch_id=? AND member_id=? AND status<>'refused'");
            $totalStmt->execute([$id,$member_id]);
            $currentTotal = (float)$totalStmt->fetchColumn();
            if(!$is_manager && $batch['price_limit'] !== null && $currentTotal + $price > $batch['price_limit']){
                $error = 'exceed';
            } else {
                $category = $_POST['category'];
                if(!in_array($category,$allowedTypes)){
                    $error='type';
                } else {
                    $orig = $_FILES['receipt']['name'];
                    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                    $tmpPath = $_FILES['receipt']['tmp_name'];
                    $orig_base = pathinfo($orig, PATHINFO_FILENAME);
                    $suffix = mt_rand(1000,9999) . '-' . time();
                    $memberInfo = $pdo->prepare("SELECT name,campus_id FROM members WHERE id=?");
                    $memberInfo->execute([$member_id]);
                    $mi = $memberInfo->fetch();
                    $orig = $orig_base . '-' . $mi['name'] . '-' . $suffix . '.' . $ext;
                    if($ext === 'pdf'){
                        $keywords=$pdo->query("SELECT keyword FROM reimbursement_prohibited_keywords")->fetchAll(PDO::FETCH_COLUMN);
                        $content=@shell_exec('pdftotext '.escapeshellarg($tmpPath).' -');
                        if(!$content){ $content=@file_get_contents($tmpPath); }
                        foreach($keywords as $kw){
                            if($kw!=='' && stripos($content,$kw)!==false){
                                $error='prohibited';
                                break;
                            }
                        }
                    }
                    if(!$error){
                        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM reimbursement_receipts WHERE batch_id=? AND member_id=? AND status<>'refused'");
                        $countStmt->execute([$id,$member_id]);
                        $index = $countStmt->fetchColumn()+1;
                        $base = $mi['name'].'-'.$mi['campus_id'].'-'.$batch['title'].'-'.$index;
                        $newname = $base . '-' . $suffix . '.' . $ext;
                        $dir = __DIR__.'/reimburse_uploads/'.$id;
                        if(!is_dir($dir)) mkdir($dir,0777,true);
                        move_uploaded_file($tmpPath, $dir.'/'.$newname);
                        $stmt = $pdo->prepare("INSERT INTO reimbursement_receipts (batch_id, member_id, original_filename, stored_filename, category, description, price) VALUES (?,?,?,?,?,?,?)");
                        $stmt->execute([$id, $member_id, $orig, $newname, $category, $description, $price]);
                        add_batch_log($pdo,$id,$_SESSION['username'],'Receipt uploaded');
                    }
                }
            }
        }
    }
}

if($is_manager || $batch['in_charge_member_id']==$member_id){
    $stmt = $pdo->prepare("SELECT r.*, m.name FROM reimbursement_receipts r JOIN members m ON r.member_id=m.id WHERE r.batch_id=? AND r.status<>'refused' ORDER BY r.id DESC");
    $stmt->execute([$id]);
    $sumMembers = $pdo->prepare("SELECT m.campus_id, m.name, SUM(r.price) AS total FROM reimbursement_receipts r JOIN members m ON r.member_id=m.id WHERE r.batch_id=? AND r.status<>'refused' GROUP BY r.member_id");
    $sumMembers->execute([$id]);
    $member_totals = $sumMembers->fetchAll();
    $sumCats = $pdo->prepare("SELECT category, SUM(price) AS total FROM reimbursement_receipts WHERE batch_id=? AND status<>'refused' GROUP BY category");
    $sumCats->execute([$id]);
    $category_totals = $sumCats->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT r.*, m.name FROM reimbursement_receipts r JOIN members m ON r.member_id=m.id WHERE r.batch_id=? AND r.member_id=? AND r.status<>'refused' ORDER BY r.id DESC");
    $stmt->execute([$id,$member_id]);
}
$receipts = $stmt->fetchAll();
$logs = [];
$totalLogs = 0;
$logPage = 1;
$totalLogPages = 1;
if($is_manager){
    $logsPerPage = 10;
    $logPage = max(1, (int)($_GET['log_page'] ?? 1));
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM reimbursement_batch_logs WHERE batch_id=?");
    $countStmt->execute([$id]);
    $totalLogs = (int)$countStmt->fetchColumn();
    $totalLogPages = max(1, (int)ceil($totalLogs / $logsPerPage));
    if($logPage > $totalLogPages){
        $logPage = $totalLogPages;
    }
    $offset = ($logPage - 1) * $logsPerPage;
    $logStmt=$pdo->prepare("SELECT operator_name, action, created_at FROM reimbursement_batch_logs WHERE batch_id=:batch_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $logStmt->bindValue(':batch_id', $id, PDO::PARAM_INT);
    $logStmt->bindValue(':limit', $logsPerPage, PDO::PARAM_INT);
    $logStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $logStmt->execute();
    $logs=$logStmt->fetchAll();
}
?>
<div class="d-flex justify-content-between mb-3">
  <h2><?= htmlspecialchars($batch['title']); ?></h2>
  <?php if($is_manager || $batch['in_charge_member_id']==$member_id): ?>
  <a class="btn btn-info" href="reimbursement_download.php?id=<?= $batch['id']; ?>" data-i18n="reimburse.action_download">Download</a>
  <?php endif; ?>
</div>
<p><strong data-i18n="reimburse.batch.incharge">In Charge:</strong> <?= htmlspecialchars($batch['in_charge_name']); ?> &nbsp; <strong data-i18n="reimburse.batch.deadline">Deadline:</strong> <?= htmlspecialchars($batch['deadline']); ?> &nbsp; <strong data-i18n="reimburse.batch.limit">Limit:</strong> <?= htmlspecialchars($batch['price_limit']); ?> &nbsp; <strong data-i18n="reimburse.batch.status">Status:</strong> <span data-i18n="reimburse.status.<?= $batch['status']; ?>"><?= htmlspecialchars($batch['status']); ?></span></p>
<p><strong data-i18n="reimburse.batch.allowed_types">Allowed Types:</strong>
<?php if($allowedTypes){ foreach($allowedTypes as $t){ echo '<span data-i18n="reimburse.category.'.$t.'">'.$t.'</span> '; } } else { echo '<span data-i18n="reimburse.batch.none">None</span>'; } ?></p>
<div class="alert alert-info border border-primary border-3 bg-light-subtle" role="status">
  <div class="d-flex justify-content-between align-items-start mb-2">
    <div class="fw-bold" data-i18n="reimburse.batch.notice.title">Batch Notice</div>
    <?php if($can_edit_notice): ?>
    <span class="badge bg-primary" data-i18n="reimburse.batch.notice.editable">Editable</span>
    <?php endif; ?>
  </div>
  <?php $hasNotice = ($batch['notice_en'] ?? '') !== '' || ($batch['notice_zh'] ?? '') !== ''; ?>
  <?php if($hasNotice): ?>
  <div class="notice-text" data-lang="en"><?= nl2br(htmlspecialchars($batch['notice_en'] ?? '')); ?></div>
  <div class="notice-text" data-lang="zh"><?= nl2br(htmlspecialchars($batch['notice_zh'] ?? '')); ?></div>
  <?php else: ?>
  <div class="text-muted" data-i18n="reimburse.batch.notice.empty">No notice yet.</div>
  <?php endif; ?>
</div>
<style>
.notice-text[data-lang]{display:none;}
html[lang="en"] .notice-text[data-lang="en"], html:not([lang]) .notice-text[data-lang="zh"]{display:block;}
html[lang="zh"] .notice-text[data-lang="zh"]{display:block;}
</style>
<?php if($can_edit_notice): ?>
<form method="post" class="card border-primary mb-4">
  <div class="card-header bg-primary text-white fw-bold" data-i18n="reimburse.batch.notice.edit_title">Edit Batch Notice</div>
  <div class="card-body">
    <input type="hidden" name="update_notice" value="1">
    <div class="mb-3">
      <label class="form-label" data-i18n="reimburse.batch.notice.en">Notice (English)</label>
      <textarea name="notice_en" class="form-control" rows="3" placeholder="" aria-describedby="noticeHelpEn"><?= htmlspecialchars($batch['notice_en'] ?? ''); ?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label" data-i18n="reimburse.batch.notice.zh">Notice (Chinese)</label>
      <textarea name="notice_zh" class="form-control" rows="3" placeholder="" aria-describedby="noticeHelpZh"><?= htmlspecialchars($batch['notice_zh'] ?? ''); ?></textarea>
    </div>
    <div class="text-muted" id="noticeHelpEn" data-i18n="reimburse.batch.notice.hint">Visible to everyone; only managers and the person in charge can edit.</div>
  </div>
  <div class="card-footer text-end">
    <button type="submit" class="btn btn-primary" data-i18n="reimburse.batch.notice.save">Save Notice</button>
  </div>
</form>
<?php endif; ?>
<?php if($is_manager || $batch['in_charge_member_id']==$member_id): ?>
<div class="alert alert-danger fw-bold" data-i18n="reimburse.batch.check_warning">You should carefully check the content of each receipt and refuse those unqualified receipts before proceeding to the next step</div>
<?php endif; ?>
<?php if(!$is_manager && !$batch_locked && !$deadline_passed): ?>
<form method="post" enctype="multipart/form-data" class="mb-4">
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.file">Receipt File</label>
    <input type="file" name="receipt" id="receipt-file" class="form-control" required>
  </div>
  <button type="button" id="auto-fill" class="btn btn-secondary mb-3" data-i18n="reimburse.batch.autofill">Auto Fill</button>
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.category">Category</label>
    <select name="category" class="form-select" required>
      <?php foreach($allowedTypes as $t): ?>
      <option value="<?= $t; ?>" data-i18n="reimburse.category.<?= $t; ?>"><?= $t; ?></option>
      <?php endforeach; ?>
    </select>
  </div>
    <div class="mb-3">
      <label class="form-label" data-i18n="reimburse.batch.description">Description</label>
      <input type="text" name="description" class="form-control" required>
    </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.price">Price</label>
    <input type="number" step="0.01" name="price" class="form-control" required>
  </div>
  <?php if($error=='exceed'): ?><div class="alert alert-danger" data-i18n="reimburse.batch.limit_exceed">Price exceeds limit</div><?php endif; ?>
  <?php if($error=='desc'): ?><div class="alert alert-danger" data-i18n="reimburse.batch.description_required">Description required</div><?php endif; ?>
  <?php if($error=='type'): ?><div class="alert alert-danger" data-i18n="reimburse.batch.type_not_allowed">Type not allowed</div><?php endif; ?>
  <?php if($error=='prohibited'): ?><div class="alert alert-danger" data-i18n="reimburse.batch.prohibited">Receipt contains prohibited content</div><?php endif; ?>
  <button type="submit" class="btn btn-primary" data-i18n="reimburse.batch.upload">Upload</button>
</form>
<?php elseif($is_manager): ?>
<div class="alert alert-warning" data-i18n="reimburse.batch.manager_no_upload">Managers cannot upload receipts</div>
<?php else: ?>
<div class="alert alert-warning" data-i18n="reimburse.batch.deadline_passed">Deadline passed or batch locked</div>
<?php endif; ?>
<table class="table table-bordered">
<tr><th data-i18n="reimburse.batch.receipt">Receipt</th><th data-i18n="reimburse.batch.category">Category</th><th data-i18n="reimburse.batch.description">Description</th><th data-i18n="reimburse.batch.price">Price</th><th data-i18n="reimburse.batch.upload_date">Upload Date</th><th data-i18n="reimburse.batch.status">Status</th><th data-i18n="reimburse.batch.uploader">Uploader</th><th data-i18n="reimburse.batch.actions">Actions</th></tr>
<?php foreach($receipts as $r): ?>
<tr>
  <td><a href="<?='reimburse_uploads/'.$id.'/'.urlencode($r['stored_filename']);?>" target="_blank"><?= htmlspecialchars($r['original_filename']); ?></a></td>
  <td><span data-i18n="reimburse.category.<?= $r['category']; ?>"><?= htmlspecialchars($r['category']); ?></span></td>
  <td><?= htmlspecialchars($r['description']); ?></td>
  <td><?= htmlspecialchars($r['price']); ?></td>
  <td><?= htmlspecialchars($r['uploaded_at']); ?></td>
  <td><span data-i18n="reimburse.status.<?= $r['status']; ?>"><?= htmlspecialchars($r['status']); ?></span></td>
  <td><?= htmlspecialchars($r['name']); ?></td>
  <td>
    <?php if($is_manager || $batch['in_charge_member_id']==$member_id): ?>
    <a class="btn btn-sm btn-warning" href="reimbursement_receipt_refuse.php?id=<?= $r['id']; ?>&batch_id=<?= $id; ?>" data-i18n="reimburse.batch.refuse" onclick="return doubleConfirm(translations[document.documentElement.lang||'zh']['reimburse.batch.confirm_refuse']);">Refuse</a>
    <?php endif; ?>
    <?php if($is_manager || ($r['member_id']==$member_id && $r['status']=='submitted' && !$batch_locked)): ?>
    <a class="btn btn-sm btn-secondary" href="reimbursement_receipt_edit.php?id=<?= $r['id']; ?>&batch_id=<?= $id; ?>" data-i18n="reimburse.batch.edit">Edit</a>
    <?php endif; ?>
    <?php if($is_manager || ($r['member_id']==$member_id && $r['status']=='submitted' && !$batch_locked)): ?>
    <a class="btn btn-sm btn-danger" href="reimbursement_receipt_delete.php?id=<?= $r['id']; ?>&batch_id=<?= $id; ?>" data-i18n="reimburse.batch.delete" onclick="return doubleConfirm(translations[document.documentElement.lang||'zh']['reimburse.batch.confirm_delete']);">Delete</a>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php if($is_manager || $batch['in_charge_member_id']==$member_id): ?>
<h4 data-i18n="reimburse.batch.total_member">Total by Member</h4>
<table class="table table-bordered">
<tr><th data-i18n="reimburse.batch.campus_id">Campus ID</th><th data-i18n="reimburse.batch.uploader">Member</th><th data-i18n="reimburse.batch.total">Total</th></tr>
<?php foreach($member_totals as $mt): ?>
<tr><td><?= htmlspecialchars($mt['campus_id']); ?></td><td><?= htmlspecialchars($mt['name']); ?></td><td><?= htmlspecialchars($mt['total']); ?></td></tr>
<?php endforeach; ?>
</table>
<h4 data-i18n="reimburse.batch.total_category">Total by Category</h4>
<table class="table table-bordered">
<tr><th data-i18n="reimburse.batch.category">Category</th><th data-i18n="reimburse.batch.total">Total</th></tr>
<?php foreach($category_totals as $ct): ?>
<tr><td><span data-i18n="reimburse.category.<?= $ct['category']; ?>"><?= htmlspecialchars($ct['category']); ?></span></td><td><?= htmlspecialchars($ct['total']); ?></td></tr>
<?php endforeach; ?>
</table>
<?php if($is_manager): ?>
<h4 data-i18n="reimburse.batch.logs">Change Log</h4>
<?php if(!empty($logs)): ?>
<ul class="list-group mb-3">
  <?php foreach($logs as $log): ?>
  <li class="list-group-item"><small><?= htmlspecialchars($log['created_at']); ?> - <?= htmlspecialchars($log['operator_name']); ?>: <?= htmlspecialchars($log['action']); ?></small></li>
  <?php endforeach; ?>
</ul>
<?php else: ?>
<div class="alert alert-info" data-i18n="reimburse.batch.logs.empty">No log entries</div>
<?php endif; ?>
<?php if($totalLogPages > 1): ?>
<?php $prevPage = max(1, $logPage - 1); $nextPage = min($totalLogPages, $logPage + 1); ?>
<nav aria-label="Batch log pagination">
  <ul class="pagination pagination-sm">
    <li class="page-item<?= $logPage <= 1 ? ' disabled' : ''; ?>">
      <a class="page-link" href="<?= htmlspecialchars('reimbursement_batch.php?'.http_build_query(['id'=>$id,'log_page'=>$prevPage])); ?>" data-i18n="reimburse.batch.logs.prev">Previous</a>
    </li>
    <li class="page-item disabled"><span class="page-link"><span data-i18n="reimburse.batch.logs.page_label">Page</span> <?= $logPage; ?>/<?= $totalLogPages; ?></span></li>
    <li class="page-item<?= $logPage >= $totalLogPages ? ' disabled' : ''; ?>">
      <a class="page-link" href="<?= htmlspecialchars('reimbursement_batch.php?'.http_build_query(['id'=>$id,'log_page'=>$nextPage])); ?>" data-i18n="reimburse.batch.logs.next">Next</a>
    </li>
  </ul>
</nav>
<?php endif; ?>
<?php endif; ?>
<form method="post" class="mt-3" id="batchForm">
  <?php if($batch['status']=='open'): ?>
  <button type="button" id="lockBtn" class="btn btn-warning" data-i18n="reimburse.batch.lock">Lock</button>
  <?php elseif($batch['status']=='locked'): ?>
  <button type="submit" name="complete" value="1" class="btn btn-success" data-i18n="reimburse.batch.complete">Complete</button>
  <button type="submit" name="unlock" value="1" class="btn btn-secondary" data-i18n="reimburse.batch.unlock">Unlock</button>
  <?php elseif($batch['status']=='completed'): ?>
  <button type="submit" name="reopen" value="1" class="btn btn-secondary" data-i18n="reimburse.batch.reopen">Reopen</button>
  <?php endif; ?>
</form>
<div class="modal fade" id="lockModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="reimburse.batch.lock">Lock</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="fw-bold text-danger" data-i18n="reimburse.batch.check_warning">Warning</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="reimburse.batch.cancel">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmLock" data-i18n="reimburse.batch.lock">Lock</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const autofillBtn=document.getElementById('auto-fill');
  if(autofillBtn){
    autofillBtn.addEventListener('click',async()=>{
      const file=document.getElementById('receipt-file');
      if(!file.files.length){
        alert(translations[document.documentElement.lang||'zh']['reimburse.batch.file_required']);
        return;
      }
      const fd=new FormData();
      fd.append('receipt',file.files[0]);
      const res=await fetch('reimbursement_autofill.php',{method:'POST',body:fd});
      const data=await res.json();
      if(data.price){
        document.querySelector('input[name="price"]').value=data.price;
      }
      if(data.category){
        document.querySelector('select[name="category"]').value=data.category;
      }
    });
  }
  const lockBtn=document.getElementById('lockBtn');
  const confirmLock=document.getElementById('confirmLock');
  const batchForm=document.getElementById('batchForm');
  if(lockBtn && confirmLock && batchForm){
    lockBtn.addEventListener('click',()=>{
      const modal=new bootstrap.Modal(document.getElementById('lockModal'));
      modal.show();
    });
    confirmLock.addEventListener('click',()=>{
      const input=document.createElement('input');
      input.type='hidden';
      input.name='lock';
      input.value='1';
      batchForm.appendChild(input);
      batchForm.submit();
    });
  }
});
</script>
<?php include 'footer.php'; ?>
