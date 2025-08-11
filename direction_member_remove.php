<?php
include 'auth.php';
$direction_id = $_GET['direction_id'] ?? null;
$member_id = $_GET['member_id'] ?? null;
if($direction_id && $member_id){
    $pdo->prepare('DELETE FROM direction_members WHERE direction_id=? AND member_id=?')->execute([$direction_id,$member_id]);
}
header('Location: direction_members.php?id='.$direction_id);
exit();
?>
