<?php
include 'auth.php';
$id = (int)($_GET['id'] ?? 0);
$batch_id = (int)($_GET['batch_id'] ?? 0);
$is_manager = ($_SESSION['role'] === 'manager');
$member_id = $_SESSION['member_id'] ?? 0;
$stmt = $pdo->prepare("SELECT r.id, b.in_charge_member_id FROM reimbursement_receipts r JOIN reimbursement_batches b ON r.batch_id=b.id WHERE r.id=?");
$stmt->execute([$id]);
$rec = $stmt->fetch();
if(!$rec || (!($is_manager) && $rec['in_charge_member_id'] != $member_id)){
    echo 'No permission';
    exit;
}
$pdo->prepare("UPDATE reimbursement_receipts SET status='refused' WHERE id=?")->execute([$id]);
header('Location: reimbursement_batch.php?id='.$batch_id);
exit;
?>
