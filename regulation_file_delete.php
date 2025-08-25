<?php
include 'auth_manager.php';
$id = (int)($_GET['id'] ?? 0);
$reg_id = (int)($_GET['reg_id'] ?? 0);
$stmt = $pdo->prepare('SELECT regulation_id, stored_filename FROM regulation_files WHERE id=?');
$stmt->execute([$id]);
if($file = $stmt->fetch()){
    @unlink(__DIR__.'/regulation_uploads/'.$file['regulation_id'].'/'.$file['stored_filename']);
    $pdo->prepare('DELETE FROM regulation_files WHERE id=?')->execute([$id]);
}
header('Location: regulation_edit.php?id='.$reg_id);
exit;
