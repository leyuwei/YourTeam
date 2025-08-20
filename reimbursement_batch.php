<?php
include 'auth.php';
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
$is_manager = ($_SESSION['role'] === 'manager');
$member_id = $_SESSION['member_id'] ?? null;
$deadline_passed = (strtotime($batch['deadline']) < strtotime(date('Y-m-d')));

if($_SERVER['REQUEST_METHOD']==='POST' && (!$deadline_passed || $is_manager)){
    if(isset($_FILES['receipt']) && $_FILES['receipt']['error']===UPLOAD_ERR_OK){
        $amount = $_POST['amount'] ?? null;
        $orig = $_FILES['receipt']['name'];
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $newname = uniqid().'.'.$ext;
        $dir = __DIR__.'/reimburse_uploads/'.$id;
        if(!is_dir($dir)) mkdir($dir,0777,true);
        move_uploaded_file($_FILES['receipt']['tmp_name'], $dir.'/'.$newname);
        $stmt = $pdo->prepare("INSERT INTO reimbursement_receipts (batch_id, member_id, original_filename, stored_filename, amount) VALUES (?,?,?,?,?)");
        $stmt->execute([$id, $member_id, $orig, $newname, $amount]);
    }
}

if($is_manager || $batch['in_charge_member_id']==$member_id){
    $stmt = $pdo->prepare("SELECT r.*, m.name FROM reimbursement_receipts r JOIN members m ON r.member_id=m.id WHERE r.batch_id=? ORDER BY r.id DESC");
    $stmt->execute([$id]);
} else {
    $stmt = $pdo->prepare("SELECT r.*, m.name FROM reimbursement_receipts r JOIN members m ON r.member_id=m.id WHERE r.batch_id=? AND r.member_id=? ORDER BY r.id DESC");
    $stmt->execute([$id,$member_id]);
}
$receipts = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between mb-3">
  <h2><?= htmlspecialchars($batch['title']); ?></h2>
  <?php if($is_manager || $batch['in_charge_member_id']==$member_id): ?>
  <a class="btn btn-info" href="reimbursement_download.php?id=<?= $batch['id']; ?>" data-i18n="reimburse.action_download">Download</a>
  <?php endif; ?>
</div>
<p><strong data-i18n="reimburse.batch.incharge">In Charge:</strong> <?= htmlspecialchars($batch['in_charge_name']); ?> &nbsp; <strong data-i18n="reimburse.batch.deadline">Deadline:</strong> <?= htmlspecialchars($batch['deadline']); ?></p>
<?php if(!$deadline_passed || $is_manager): ?>
<form method="post" enctype="multipart/form-data" class="mb-4">
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.file">Receipt File</label>
    <input type="file" name="receipt" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.amount">Amount</label>
    <input type="number" step="0.01" name="amount" class="form-control">
  </div>
  <button type="submit" class="btn btn-primary" data-i18n="reimburse.batch.upload">Upload</button>
</form>
<?php else: ?>
<div class="alert alert-warning" data-i18n="reimburse.batch.deadline_passed">Deadline passed</div>
<?php endif; ?>
<table class="table table-bordered">
<tr><th data-i18n="reimburse.batch.receipt">Receipt</th><th data-i18n="reimburse.batch.amount">Amount</th><th data-i18n="reimburse.batch.uploader">Uploader</th><th data-i18n="reimburse.batch.actions">Actions</th></tr>
<?php foreach($receipts as $r): ?>
<tr>
  <td><a href="<?='reimburse_uploads/'.$id.'/'.urlencode($r['stored_filename']);?>" target="_blank"><?= htmlspecialchars($r['original_filename']); ?></a></td>
  <td><?= htmlspecialchars($r['amount']); ?></td>
  <td><?= htmlspecialchars($r['name']); ?></td>
  <td>
    <?php if($is_manager || ($r['member_id']==$member_id && !$deadline_passed)): ?>
    <a class="btn btn-sm btn-danger" href="reimbursement_receipt_delete.php?id=<?= $r['id']; ?>&batch_id=<?= $id; ?>" data-i18n="reimburse.batch.delete" onclick="return doubleConfirm(translations[document.documentElement.lang||'en']['reimburse.batch.confirm_delete']);">Delete</a>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php include 'footer.php'; ?>
