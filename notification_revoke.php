<?php
include 'auth_manager.php';
$id = $_GET['id'] ?? null;
if($id){
    $stmt = $pdo->prepare('UPDATE notifications SET is_revoked=1 WHERE id=?');
    $stmt->execute([$id]);
}
header('Location: notifications.php');
exit();
?>
