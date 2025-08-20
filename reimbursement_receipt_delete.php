<?php
include 'auth.php';
$id = (int)($_GET['id'] ?? 0);
$batch_id = (int)($_GET['batch_id'] ?? 0);
$stmt = $pdo->prepare("SELECT r.*, b.deadline FROM reimbursement_receipts r JOIN reimbursement_batches b ON r.batch_id=b.id WHERE r.id=?");
$stmt->execute([$id]);
$rec = $stmt->fetch();
if($rec){
    $is_manager = ($_SESSION['role'] === 'manager');
    $member_id = $_SESSION['member_id'] ?? null;
    $deadline_passed = (strtotime($rec['deadline']) < strtotime(date('Y-m-d')));
    if($is_manager || ($rec['member_id']==$member_id && !$deadline_passed)){
        $file = __DIR__.'/reimburse_uploads/'.$rec['batch_id'].'/'.$rec['stored_filename'];
        if(is_file($file)) unlink($file);
        $del = $pdo->prepare("DELETE FROM reimbursement_receipts WHERE id=?");
        $del->execute([$id]);
    }
}
header('Location: reimbursement_batch.php?id='.$batch_id);
exit;
