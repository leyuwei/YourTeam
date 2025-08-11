<?php
include 'auth.php';
$id = $_GET['id'] ?? null;
if($id){
    $pdo->prepare('DELETE FROM research_directions WHERE id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM direction_members WHERE direction_id=?')->execute([$id]);
}
header('Location: directions.php');
exit();
?>
