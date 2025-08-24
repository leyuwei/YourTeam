<?php
include 'auth_manager.php';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $task_id = $_POST['task_id'];
    $ids = $_POST['affair_ids'] ?? [];
    if(count($ids) < 2){
        header('Location: task_affairs.php?id=' . $task_id);
        exit();
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM task_affairs WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $affairs = $stmt->fetchAll();
    if(!$affairs){
        header('Location: task_affairs.php?id=' . $task_id);
        exit();
    }
    $descriptions = array_column($affairs, 'description');
    $description = implode('; ', $descriptions);
    $start_time = min(array_column($affairs, 'start_time'));
    $end_time = max(array_column($affairs, 'end_time'));
    $memberStmt = $pdo->prepare("SELECT DISTINCT member_id FROM task_affair_members WHERE affair_id IN ($placeholders)");
    $memberStmt->execute($ids);
    $members = $memberStmt->fetchAll(PDO::FETCH_COLUMN);
    $pdo->prepare('INSERT INTO task_affairs(task_id,description,start_time,end_time,status) VALUES (?,?,?,?,?)')
        ->execute([$task_id,$description,$start_time,$end_time,'pending']);
    $new_id = $pdo->lastInsertId();
    $insert = $pdo->prepare('INSERT INTO task_affair_members(affair_id,member_id) VALUES (?,?)');
    foreach($members as $mid){
        $insert->execute([$new_id,$mid]);
    }
    $pdo->prepare("DELETE FROM task_affair_members WHERE affair_id IN ($placeholders)")->execute($ids);
    $pdo->prepare("DELETE FROM task_affairs WHERE id IN ($placeholders)")->execute($ids);
    header('Location: task_affairs.php?id=' . $task_id);
    exit();
}
header('Location: tasks.php');
?>
