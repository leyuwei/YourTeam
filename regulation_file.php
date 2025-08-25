<?php
include 'auth.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT rf.*, r.id AS reg_id FROM regulation_files rf JOIN regulations r ON rf.regulation_id=r.id WHERE rf.id=?');
$stmt->execute([$id]);
$file = $stmt->fetch();
if(!$file){
    exit('File not found');
}
$path = __DIR__.'/regulation_uploads/'.$file['reg_id'].'/'.$file['stored_filename'];
if(!is_file($path)){
    exit('File not found');
}
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.basename($file['original_filename']).'"');
readfile($path);
exit;
