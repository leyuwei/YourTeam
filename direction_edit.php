<?php
require 'auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $default = ['id' => null, 'title' => '', 'description' => '', 'bg_color' => '#ffffff'];
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM research_directions WHERE id=?');
        $stmt->execute([$id]);
        $direction = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($direction) {
            $default = array_merge($default, $direction);
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'direction' => $default], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: directions.php');
    exit;
}

$data = $_POST;
if (empty($data) && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
}

$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$bg_color = trim($data['bg_color'] ?? '#ffffff');

if ($title === '') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'code' => 'title_required', 'message' => 'Title is required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($bg_color && !preg_match('/^#[0-9a-fA-F]{6}$/', $bg_color)) {
    $bg_color = '#ffffff';
}

if (!empty($data['id'])) {
    $recordId = (int)$data['id'];
    $stmt = $pdo->prepare('UPDATE research_directions SET title=?, description=?, bg_color=? WHERE id=?');
    $stmt->execute([$title,$description,$bg_color,$recordId]);
    $savedId = $recordId;
} else {
    $orderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order),-1)+1 FROM research_directions');
    $nextOrder = (int)$orderStmt->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO research_directions(title,description,bg_color,sort_order) VALUES (?,?,?,?)');
    $stmt->execute([$title,$description,$bg_color,$nextOrder]);
    $savedId = (int)$pdo->lastInsertId();
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'id' => $savedId], JSON_UNESCAPED_UNICODE);
exit;
