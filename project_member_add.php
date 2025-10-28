<?php
include_once 'auth.php';

$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$isAjax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (strpos($acceptHeader, 'application/json') !== false)
);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $project_id = $_POST['project_id'] ?? null;
    $member_id = $_POST['member_id'] ?? null;
    $join_time = $_POST['join_time'] ?? null;

    if(!$project_id || !$member_id || !$join_time){
        if ($isAjax) {
            header('Content-Type: application/json', true, 422);
            echo json_encode(['status' => 'error', 'error_key' => 'project_members.error_add'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        header('Location: projects.php');
        exit();
    }

    $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order),-1)+1 FROM project_member_log WHERE project_id=? AND exit_time IS NULL');
    $orderStmt->execute([$project_id]);
    $nextOrder = $orderStmt->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO project_member_log(project_id,member_id,join_time,sort_order) VALUES (?,?,?,?)');
    $stmt->execute([$project_id,$member_id,$join_time,$nextOrder]);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    header('Location: project_members.php?id='.$project_id);
    exit();
}

if ($isAjax) {
    header('Content-Type: application/json', true, 405);
    echo json_encode(['status' => 'error', 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit();
}

header('Location: projects.php');
?>
