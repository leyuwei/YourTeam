<?php
include 'auth.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $task_id = $_POST['task_id'];
    $description = $_POST['description'];
    $member_ids = $_POST['member_ids'] ?? [];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $stmt = $pdo->prepare('INSERT INTO task_affairs(task_id,description,start_time,end_time) VALUES (?,?,?,?)');
    $stmt->execute([$task_id,$description,$start_time,$end_time]);
    $affair_id = $pdo->lastInsertId();
    foreach($member_ids as $mid){
        $pdo->prepare('INSERT INTO task_affair_members(affair_id,member_id) VALUES (?,?)')->execute([$affair_id,$mid]);
    }
    header('Location: task_affairs.php?id='.$task_id);
    exit();
}
header('Location: tasks.php');
?>
