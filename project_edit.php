<?php
require 'auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $default = ['id' => null, 'title' => '', 'description' => '', 'bg_color' => '#ffffff', 'begin_date' => '', 'end_date' => '', 'status' => 'todo'];
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id=?');
        $stmt->execute([$id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($project) {
            $default = array_merge($default, $project);
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'project' => $default], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: projects.php');
    exit;
}

$data = $_POST;
if (empty($data) && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
}

$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$bg_color = trim($data['bg_color'] ?? '#ffffff');
$begin_date = trim($data['begin_date'] ?? '');
$end_date = trim($data['end_date'] ?? '');
$status = trim($data['status'] ?? 'todo');

if ($title === '') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'code' => 'title_required', 'message' => 'Title is required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($bg_color && !preg_match('/^#[0-9a-fA-F]{6}$/', $bg_color)) {
    $bg_color = '#ffffff';
}

if ($begin_date && $end_date && strtotime($end_date) <= strtotime($begin_date)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'code' => 'date_range', 'message' => 'End date must be after begin date.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$allowedStatuses = ['todo','ongoing','paused','finished'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'todo';
}

if (!empty($data['id'])) {
    $recordId = (int)$data['id'];
    $stmt = $pdo->prepare('UPDATE projects SET title=?, description=?, bg_color=?, begin_date=?, end_date=?, status=? WHERE id=?');
    $stmt->execute([$title,$description,$bg_color,$begin_date,$end_date,$status,$recordId]);
    $savedId = $recordId;
} else {
    $orderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order),-1)+1 FROM projects');
    $nextOrder = (int)$orderStmt->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO projects(title,description,bg_color,begin_date,end_date,status,sort_order) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$title,$description,$bg_color,$begin_date,$end_date,$status,$nextOrder]);
    $savedId = (int)$pdo->lastInsertId();
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'id' => $savedId], JSON_UNESCAPED_UNICODE);
exit;
