<?php
include 'auth.php';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$acceptsJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

$direction_id = $_POST['direction_id'] ?? $_GET['direction_id'] ?? null;
$member_id = $_POST['member_id'] ?? $_GET['member_id'] ?? null;

if ($direction_id && $member_id) {
    $pdo->prepare('DELETE FROM direction_members WHERE direction_id=? AND member_id=?')->execute([$direction_id, $member_id]);

    if ($isAjax || $acceptsJson) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
        exit();
    }

    header('Location: direction_members.php?id=' . $direction_id);
    exit();
}

if ($isAjax || $acceptsJson) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error']);
    exit();
}

header('Location: directions.php');
exit();
?>
