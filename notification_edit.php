<?php
include 'auth_manager.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function getNotificationMembers(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, name FROM members WHERE status='in_work' ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $notification = [
        'id' => null,
        'content' => '',
        'valid_begin_date' => date('Y-m-d'),
        'valid_end_date' => date('Y-m-d', strtotime('+7 days')),
        'members' => [],
    ];
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM notifications WHERE id=?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $notification = array_merge($notification, $row);
            $targetsStmt = $pdo->prepare('SELECT member_id FROM notification_targets WHERE notification_id=?');
            $targetsStmt->execute([$id]);
            $notification['members'] = array_map('intval', $targetsStmt->fetchAll(PDO::FETCH_COLUMN));
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'notification' => $notification,
        'members' => getNotificationMembers($pdo),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid request method.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: notifications.php');
    exit;
}

$payload = file_get_contents('php://input');
$data = [];
if ($payload !== false && trim($payload) !== '') {
    $data = json_decode($payload, true) ?? [];
}
if (empty($data)) {
    $data = $_POST;
}

$content = trim($data['content'] ?? '');
$begin = trim($data['valid_begin_date'] ?? '');
$end = trim($data['valid_end_date'] ?? '');
$membersSelected = $data['members'] ?? [];
if (!is_array($membersSelected)) {
    $membersSelected = [];
}
$membersSelected = array_values(array_unique(array_map('intval', $membersSelected)));

if ($content === '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Content is required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$beginDate = DateTime::createFromFormat('Y-m-d', $begin) ?: null;
$endDate = DateTime::createFromFormat('Y-m-d', $end) ?: null;
if (!$beginDate || !$endDate) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Invalid date format.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($beginDate > $endDate) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Begin date must not be after end date.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo->beginTransaction();
    if ($id) {
        $stmt = $pdo->prepare('UPDATE notifications SET content=?, valid_begin_date=?, valid_end_date=? WHERE id=?');
        $stmt->execute([$content, $beginDate->format('Y-m-d'), $endDate->format('Y-m-d'), $id]);
        $pdo->prepare('DELETE FROM notification_targets WHERE notification_id=?')->execute([$id]);
        $notificationId = $id;
    } else {
        $stmt = $pdo->prepare('INSERT INTO notifications(content, valid_begin_date, valid_end_date) VALUES (?,?,?)');
        $stmt->execute([$content, $beginDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        $notificationId = (int)$pdo->lastInsertId();
    }
    if (!empty($membersSelected)) {
        $insertStmt = $pdo->prepare('INSERT INTO notification_targets(notification_id, member_id) VALUES (?,?)');
        foreach ($membersSelected as $memberId) {
            $insertStmt->execute([$notificationId, $memberId]);
        }
    }
    $pdo->commit();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'id' => $notificationId], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    $pdo->rollBack();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Failed to save notification.'], JSON_UNESCAPED_UNICODE);
    exit;
}
