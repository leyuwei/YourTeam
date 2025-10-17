<?php
require 'auth.php';
require_once 'xlsx_helper.php';

function response_json(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest' || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        response_json(['success' => false, 'message' => 'Invalid request method.'], 405);
    }
    header('Location: projects.php');
    exit;
}

if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    response_json(['success' => false, 'message' => 'No file uploaded.'], 400);
}

try {
    $rows = xlsx_parse_rows($_FILES['file']['tmp_name']);
} catch (Throwable $e) {
    response_json(['success' => false, 'message' => 'Failed to read XLSX file.'], 400);
}

if (empty($rows)) {
    response_json(['success' => false, 'message' => 'XLSX file is empty.'], 400);
}

$header = array_map('trim', $rows[0]);
$mapCandidates = [
    'id' => ['id', '项目编号', 'Project ID'],
    'title' => ['title', '项目名称', 'Title'],
    'description' => ['description', '项目描述', 'Description'],
    'begin_date' => ['begin_date', '开始日期', 'Begin Date'],
    'end_date' => ['end_date', '结束日期', 'End Date'],
    'status' => ['status', '状态', 'Status'],
    'bg_color' => ['bg_color', '背景色', 'Background Color'],
];

$headerMap = [];
foreach ($mapCandidates as $field => $names) {
    foreach ($header as $index => $label) {
        if ($label === '') {
            continue;
        }
        if (in_array(strtolower($label), array_map('strtolower', $names), true)) {
            $headerMap[$field] = $index;
            break;
        }
    }
}

if (!isset($headerMap['title'])) {
    response_json(['success' => false, 'message' => 'Missing title column.'], 400);
}

$pdo->beginTransaction();
$inserted = 0;
$updated = 0;
$statusAllowed = ['todo', 'ongoing', 'paused', 'finished'];
try {
    $orderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM projects');
    $nextOrder = (int)$orderStmt->fetchColumn();
    $updateStmt = $pdo->prepare('UPDATE projects SET title=?, description=?, begin_date=?, end_date=?, status=?, bg_color=? WHERE id=?');
    $insertStmt = $pdo->prepare('INSERT INTO projects(title, description, begin_date, end_date, status, bg_color, sort_order) VALUES (?,?,?,?,?,?,?)');
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        if ($row === null || count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
            continue;
        }
        $data = [];
        foreach ($headerMap as $field => $idx) {
            $data[$field] = $row[$idx] ?? '';
        }
        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            continue;
        }
        $description = trim((string)($data['description'] ?? ''));
        $begin = trim((string)($data['begin_date'] ?? ''));
        $end = trim((string)($data['end_date'] ?? ''));
        $status = strtolower(trim((string)($data['status'] ?? 'todo')));
        if (!in_array($status, $statusAllowed, true)) {
            $status = 'todo';
        }
        $bgColor = trim((string)($data['bg_color'] ?? ''));
        if ($bgColor !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $bgColor)) {
            $bgColor = '#ffffff';
        }
        $id = isset($data['id']) ? trim((string)$data['id']) : '';
        if ($id !== '') {
            $id = (int)$id;
            $updateStmt->execute([$title, $description, $begin, $end, $status, $bgColor, $id]);
            if ($updateStmt->rowCount() > 0) {
                $updated++;
                continue;
            }
        }
        $insertStmt->execute([$title, $description, $begin, $end, $status, $bgColor, $nextOrder++]);
        $inserted++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    response_json(['success' => false, 'message' => 'Failed to import projects.'], 500);
}

response_json([
    'success' => true,
    'inserted' => $inserted,
    'updated' => $updated,
]);
