<?php
include 'auth_manager.php';
$id = (int)($_GET['id'] ?? 0);
if($id){
    $dir = __DIR__ . '/reimburse_uploads/' . $id;
    if(is_dir($dir)){
        foreach(glob($dir.'/*') as $file){
            if(is_file($file)) unlink($file);
        }
        rmdir($dir);
    }
    $pdo->prepare('DELETE FROM reimbursement_receipts WHERE batch_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM reimbursement_batches WHERE id=?')->execute([$id]);
}
header('Location: reimbursements.php');
exit;
