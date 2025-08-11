<?php
include 'auth.php';
$id = $_GET['id'] ?? null;
if($id){
    $pdo->prepare('DELETE FROM tasks WHERE id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM task_affairs WHERE task_id=?')->execute([$id]);
}
header('Location: tasks.php');
exit();
?>
