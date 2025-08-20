<?php
include 'auth.php';
if($_SESSION['role'] !== 'member'){
    header('Location: index.php');
    exit();
}
$id = $_GET['id'] ?? null;
if($id){
    $stmt = $pdo->prepare('UPDATE notification_targets SET status="checked" WHERE notification_id=? AND member_id=?');
    $stmt->execute([$id, $_SESSION['member_id']]);
}
header('Location: index.php');
exit();
?>
