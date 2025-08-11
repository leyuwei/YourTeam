<?php
include 'auth.php';
$id = $_GET['id'] ?? null;
if($id){
    $pdo->prepare('DELETE FROM projects WHERE id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM project_member_log WHERE project_id=?')->execute([$id]);
}
header('Location: projects.php');
exit();
?>
