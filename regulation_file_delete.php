<?php
include 'auth_manager.php';
$id = (int)($_GET['id'] ?? 0);
$reg_id = (int)($_GET['reg_id'] ?? 0);
$stmt = $pdo->prepare('SELECT regulation_id, stored_filename FROM regulation_files WHERE id=?');
$stmt->execute([$id]);
if($file = $stmt->fetch()){
    $reg_id = $reg_id ?: (int)$file['regulation_id'];
    @unlink(__DIR__.'/regulation_uploads/'.$file['regulation_id'].'/'.$file['stored_filename']);
    $pdo->prepare('DELETE FROM regulation_files WHERE id=?')->execute([$id]);
}
$redirect = $reg_id ? 'regulation_edit.php?id='.$reg_id : 'notifications.php';
header('Location: '.$redirect);
exit;
