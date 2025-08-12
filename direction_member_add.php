<?php
include 'auth.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $direction_id = $_POST['direction_id'];
    $member_id = $_POST['member_id'];
    $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order),-1)+1 FROM direction_members WHERE direction_id=?');
    $orderStmt->execute([$direction_id]);
    $nextOrder = $orderStmt->fetchColumn();
    $stmt = $pdo->prepare('INSERT IGNORE INTO direction_members(direction_id,member_id,sort_order) VALUES (?,?,?)');
    $stmt->execute([$direction_id,$member_id,$nextOrder]);
    header('Location: direction_members.php?id='.$direction_id);
    exit();
}
header('Location: directions.php');
exit();
?>
