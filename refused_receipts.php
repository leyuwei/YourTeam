<?php
include 'auth.php';
include 'header.php';
$member_id = $_SESSION['member_id'] ?? 0;
$stmt = $pdo->prepare("SELECT r.*, b.title AS batch_title FROM reimbursement_receipts r JOIN reimbursement_batches b ON r.batch_id=b.id WHERE r.member_id=? AND r.status='refused' ORDER BY r.id DESC");
$stmt->execute([$member_id]);
$receipts = $stmt->fetchAll();
?>
<h2 data-i18n="reimburse.refused.title">Refused Receipts</h2>
<?php if($receipts): ?>
<table class="table table-bordered">
<tr><th data-i18n="reimburse.batch.receipt">Receipt</th><th data-i18n="reimburse.batch.category">Category</th><th data-i18n="reimburse.batch.description">Description</th><th data-i18n="reimburse.batch.price">Price</th><th data-i18n="reimburse.refused.original_batch">Original Batch</th><th data-i18n="reimburse.batch.actions">Actions</th></tr>
<?php foreach($receipts as $r): ?>
<tr>
  <td><a href="<?= 'reimburse_uploads/'.$r['batch_id'].'/'.urlencode($r['stored_filename']); ?>" target="_blank"><?= htmlspecialchars($r['stored_filename']); ?></a></td>
  <td><span data-i18n="reimburse.category.<?= $r['category']; ?>"><?= htmlspecialchars($r['category']); ?></span></td>
  <td><?= htmlspecialchars($r['description']); ?></td>
  <td><?= htmlspecialchars($r['price']); ?></td>
  <td><?= htmlspecialchars($r['batch_title']); ?></td>
  <td><a class="btn btn-sm btn-secondary" href="reimbursement_receipt_edit.php?id=<?= $r['id']; ?>" data-i18n="reimburse.batch.edit">Edit</a></td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<div class="alert alert-info" data-i18n="reimburse.batch.none">None</div>
<?php endif; ?>
<?php include 'footer.php'; ?>
