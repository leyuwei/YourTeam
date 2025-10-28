<?php
include 'auth.php';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$acceptsJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $direction_id = $_POST['direction_id'] ?? null;
    $member_id = $_POST['member_id'] ?? null;

    if ($direction_id && $member_id) {
        $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order),-1)+1 FROM direction_members WHERE direction_id=?');
        $orderStmt->execute([$direction_id]);
        $nextOrder = $orderStmt->fetchColumn();

        $stmt = $pdo->prepare('INSERT IGNORE INTO direction_members(direction_id,member_id,sort_order) VALUES (?,?,?)');
        $stmt->execute([$direction_id, $member_id, $nextOrder]);
        $added = $stmt->rowCount() > 0;

        if ($isAjax || $acceptsJson) {
            header('Content-Type: application/json');
            if ($added) {
                $memberStmt = $pdo->prepare('SELECT id, campus_id, name FROM members WHERE id=?');
                $memberStmt->execute([$member_id]);
                $member = $memberStmt->fetch();

                echo json_encode([
                    'status' => 'ok',
                    'member' => $member,
                ]);
            } else {
                echo json_encode(['status' => 'exists']);
            }
            exit();
        }

        header('Location: direction_members.php?id=' . $direction_id);
        exit();
    }
}

if ($isAjax || $acceptsJson) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error']);
    exit();
}

header('Location: directions.php');
exit();
?>
