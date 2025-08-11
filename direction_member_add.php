<?php
include 'auth.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $direction_id = $_POST['direction_id'];
    $member_id = $_POST['member_id'];
    $pdo->prepare('INSERT IGNORE INTO direction_members(direction_id,member_id) VALUES (?,?)')->execute([$direction_id,$member_id]);
    header('Location: direction_members.php?id='.$direction_id);
    exit();
}
header('Location: directions.php');
exit();
?>
