<?php
include 'auth_manager.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function buildTaskPayload(PDO $pdo, ?int $id): array
{
    $task = [
        'id' => null,
        'title' => '',
        'description' => '',
        'start_date' => '',
        'status' => 'active',
    ];
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id=?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $task = array_merge($task, $row);
        }
    }
    return $task;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'task' => buildTaskPayload($pdo, $id)], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid request method.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: tasks.php');
    exit;
}

$raw = file_get_contents('php://input');
$data = [];
if ($raw !== false && trim($raw) !== '') {
    $data = json_decode($raw, true) ?? [];
}
if (empty($data)) {
    $data = $_POST;
}

$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$start_date = trim($data['start_date'] ?? '');
$status = trim($data['status'] ?? 'active');
$status = in_array($status, ['active', 'paused', 'finished'], true) ? $status : 'active';

if ($title === '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Title is required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($start_date !== '') {
    $startDateObj = DateTime::createFromFormat('Y-m-d', $start_date);
    if (!$startDateObj) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid start date.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $start_date = $startDateObj->format('Y-m-d');
}

if ($id) {
    $stmt = $pdo->prepare('UPDATE tasks SET title=?, description=?, start_date=?, status=? WHERE id=?');
    $stmt->execute([$title, $description, $start_date ?: null, $status, $id]);
    $savedId = $id;
    $redirect = null;
} else {
    $stmt = $pdo->prepare('INSERT INTO tasks(title, description, start_date, status) VALUES (?,?,?,?)');
    $stmt->execute([$title, $description, $start_date ?: null, $status]);
    $savedId = (int)$pdo->lastInsertId();
    $redirect = $savedId ? ('task_affairs.php?id=' . $savedId) : null;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'id' => $savedId, 'redirect' => $redirect], JSON_UNESCAPED_UNICODE);
exit;
