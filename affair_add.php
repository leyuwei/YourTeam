<?php
include 'auth.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $task_id = $_POST['task_id'];
    $description = $_POST['description'];
    $member_id = $_POST['member_id'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $stmt = $pdo->prepare('INSERT INTO task_affairs(task_id,description,member_id,start_time,end_time) VALUES (?,?,?,?,?)');
    $stmt->execute([$task_id,$description,$member_id,$start_time,$end_time]);
    header('Location: task_affairs.php?id='.$task_id);
    exit();
}
header('Location: tasks.php');
?>
