<?php
include 'auth_manager.php';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = $_POST['id'];
    $task_id = $_POST['task_id'];
    $description = $_POST['description'];
    $member_ids = $_POST['member_ids'] ?? [];
    $start_date = $_POST['start_time'];
    $end_date = $_POST['end_time'];
    if(strtotime($end_date) < strtotime($start_date)){
        echo '结束日期必须不早于起始日期';
        exit();
    }
    $start_time = $start_date . ' 00:00:00';
    $end_time = date('Y-m-d 00:00:00', strtotime($end_date . ' +1 day'));
    $stmt = $pdo->prepare('UPDATE task_affairs SET description=?, start_time=?, end_time=? WHERE id=?');
    $stmt->execute([$description,$start_time,$end_time,$id]);
    $pdo->prepare('DELETE FROM task_affair_members WHERE affair_id=?')->execute([$id]);
    $insert = $pdo->prepare('INSERT INTO task_affair_members(affair_id,member_id) VALUES (?,?)');
    foreach($member_ids as $mid){
        $insert->execute([$id,$mid]);
    }
    header('Location: task_affairs.php?id=' . $task_id);
    exit();
}
header('Location: tasks.php');
?>
