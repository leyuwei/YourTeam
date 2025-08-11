<?php
include 'auth.php';
$id = $_GET['id'] ?? null;
$task_id = $_GET['task_id'] ?? null;
if($id){
    $pdo->prepare('DELETE FROM task_affairs WHERE id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM task_affair_members WHERE affair_id=?')->execute([$id]);
}
if($task_id){
    header('Location: task_affairs.php?id='.$task_id);
} else {
    header('Location: tasks.php');
}
exit();
?>
