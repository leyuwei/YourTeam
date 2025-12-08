<?php
include 'auth_manager.php';
$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$id = (int)($_POST['id'] ?? $payload['id'] ?? $_GET['id'] ?? 0);
$reg_id = (int)($_POST['reg_id'] ?? $payload['reg_id'] ?? $_GET['reg_id'] ?? 0);
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

$stmt = $pdo->prepare('SELECT regulation_id, stored_filename FROM regulation_files WHERE id=?');
$stmt->execute([$id]);
$result = ['success' => false, 'error' => 'File not found'];
if($file = $stmt->fetch()){
    $reg_id = $reg_id ?: (int)$file['regulation_id'];
    @unlink(__DIR__.'/regulation_uploads/'.$file['regulation_id'].'/'.$file['stored_filename']);
    $pdo->prepare('DELETE FROM regulation_files WHERE id=?')->execute([$id]);
    $result = ['success' => true, 'reg_id' => $reg_id];
}

if($isAjax){
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

$target = $reg_id ? 'regulation_edit.php?id='.$reg_id : 'notifications.php';
header('Location: '.$target);
exit;
