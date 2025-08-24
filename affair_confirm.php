<?php
include 'auth_manager.php';
$id = $_GET['id'] ?? null;
$task_id = $_GET['task_id'] ?? null;
$status = $_GET['status'] ?? null;
if($id && $task_id && in_array($status, ['pending','confirmed'])){
    $stmt = $pdo->prepare('UPDATE task_affairs SET status=? WHERE id=?');
    $stmt->execute([$status, $id]);
}
header('Location: task_affairs.php?id=' . $task_id);
exit();
?>
