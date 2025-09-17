<?php
include 'auth.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'office_view.message.error']);
    exit();
}
$seatId = isset($_POST['seat_id']) ? (int)$_POST['seat_id'] : 0;
$action = $_POST['action'] ?? '';
$targetMemberId = isset($_POST['member_id']) ? (int)$_POST['member_id'] : null;

if ($seatId <= 0) {
    echo json_encode(['success' => false, 'message' => 'office_view.message.error']);
    exit();
}
$seatStmt = $pdo->prepare('SELECT s.id, s.member_id FROM office_seats s WHERE s.id = ?');
$seatStmt->execute([$seatId]);
$seat = $seatStmt->fetch();
if (!$seat) {
    echo json_encode(['success' => false, 'message' => 'office_view.message.error']);
    exit();
}

try {
    if ($action === 'assign') {
        if ($_SESSION['role'] === 'member') {
            $targetMemberId = $_SESSION['member_id'];
        }
        if (!$targetMemberId) {
            echo json_encode(['success' => false, 'message' => 'office_view.message.select_member']);
            exit();
        }
        if ($_SESSION['role'] === 'member' && $seat['member_id'] && (int)$seat['member_id'] !== (int)$targetMemberId) {
            echo json_encode(['success' => false, 'message' => 'office_view.message.unavailable']);
            exit();
        }
        $memberStmt = $pdo->prepare("SELECT id, name FROM members WHERE id = ? AND status != 'exited'");
        $memberStmt->execute([$targetMemberId]);
        $member = $memberStmt->fetch();
        if (!$member) {
            echo json_encode(['success' => false, 'message' => 'office_view.message.error']);
            exit();
        }
        $update = $pdo->prepare('UPDATE office_seats SET member_id = ? WHERE id = ?');
        $update->execute([$targetMemberId, $seatId]);
        echo json_encode([
            'success' => true,
            'seat' => [
                'id' => $seatId,
                'member_id' => $targetMemberId,
                'member_name' => $member['name']
            ]
        ]);
        exit();
    }
    if ($action === 'release') {
        if ($_SESSION['role'] === 'member' && (int)$seat['member_id'] !== (int)($_SESSION['member_id'] ?? 0)) {
            echo json_encode(['success' => false, 'message' => 'office_view.message.no_permission']);
            exit();
        }
        $update = $pdo->prepare('UPDATE office_seats SET member_id = NULL WHERE id = ?');
        $update->execute([$seatId]);
        echo json_encode([
            'success' => true,
            'seat' => [
                'id' => $seatId,
                'member_id' => null,
                'member_name' => null
            ]
        ]);
        exit();
    }
    echo json_encode(['success' => false, 'message' => 'office_view.message.error']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'office_view.message.error']);
}
