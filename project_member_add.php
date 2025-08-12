<?php
include 'auth.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $project_id = $_POST['project_id'];
    $member_id = $_POST['member_id'];
    $join_time = $_POST['join_time'];
    $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order),-1)+1 FROM project_member_log WHERE project_id=? AND exit_time IS NULL');
    $orderStmt->execute([$project_id]);
    $nextOrder = $orderStmt->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO project_member_log(project_id,member_id,join_time,sort_order) VALUES (?,?,?,?)');
    $stmt->execute([$project_id,$member_id,$join_time,$nextOrder]);
    header('Location: project_members.php?id='.$project_id);
    exit();
}
header('Location: projects.php');
?>
