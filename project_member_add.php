<?php
include 'auth.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $project_id = $_POST['project_id'];
    $member_id = $_POST['member_id'];
    $join_time = $_POST['join_time'];
    $stmt = $pdo->prepare('INSERT INTO project_member_log(project_id,member_id,join_time) VALUES (?,?,?)');
    $stmt->execute([$project_id,$member_id,$join_time]);
    header('Location: project_members.php?id='.$project_id);
    exit();
}
header('Location: projects.php');
?>
