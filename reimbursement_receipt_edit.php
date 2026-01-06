<?php
include 'auth.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT batch_id FROM reimbursement_receipts WHERE id=?");
$stmt->execute([$id]);
$batch_id = (int)$stmt->fetchColumn();
if($batch_id){
    header('Location: reimbursement_batch.php?id='.$batch_id.'&edit_receipt_id='.$id);
    exit;
}
include 'header.php';
echo '<div class="alert alert-warning">Receipt not found</div>';
include 'footer.php';
