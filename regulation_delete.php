<?php
include 'auth_manager.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id FROM regulations WHERE id=?');
$stmt->execute([$id]);
if($stmt->fetch()){
    $files = $pdo->prepare('SELECT stored_filename FROM regulation_files WHERE regulation_id=?');
    $files->execute([$id]);
    foreach($files as $f){
        @unlink(__DIR__.'/regulation_uploads/'.$id.'/'.$f['stored_filename']);
    }
    @rmdir(__DIR__.'/regulation_uploads/'.$id);
    $pdo->prepare('DELETE FROM regulations WHERE id=?')->execute([$id]);
}
header('Location: notifications.php');
exit;
