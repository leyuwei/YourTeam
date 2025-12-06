<?php
include 'auth.php';
header('Content-Type: application/json');

if (($_SESSION['role'] ?? '') !== 'manager') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $pdo->query('SELECT id, content, keywords, updated_at FROM askme_entries ORDER BY updated_at DESC')->fetchAll();
    echo json_encode(['entries' => $rows]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

if ($action === 'save') {
    $content = trim($input['content'] ?? '');
    $keywords = trim($input['keywords'] ?? '');
    if ($content === '') {
        echo json_encode(['error' => 'content_required']);
        exit;
    }
    if (!empty($input['id'])) {
        $stmt = $pdo->prepare('UPDATE askme_entries SET content = ?, keywords = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$content, $keywords, (int)$input['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO askme_entries (content, keywords) VALUES (?, ?)');
        $stmt->execute([$content, $keywords]);
    }
    echo json_encode(['status' => 'ok']);
    exit;
}

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('DELETE FROM askme_entries WHERE id = ?');
        $stmt->execute([$id]);
    }
    echo json_encode(['status' => 'ok']);
    exit;
}

echo json_encode(['error' => 'invalid_action']);
