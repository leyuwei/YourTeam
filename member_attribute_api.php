<?php
require 'auth_manager.php';
require_once 'member_attribute_helpers.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $attributes = fetch_member_attributes($pdo);
    echo json_encode(['success' => true, 'attributes' => $attributes], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = file_get_contents('php://input');
$data = [];
if ($input !== false && trim($input) !== '') {
    $data = json_decode($input, true) ?? [];
}
if (empty($data)) {
    $data = $_POST;
}
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $labelZh = trim($data['label_zh'] ?? '');
            $labelEn = trim($data['label_en'] ?? '');
            $default = $data['default_value'] ?? '';
            $fieldType = $data['field_type'] ?? 'text';
            if ($labelZh === '' && $labelEn === '') {
                throw new RuntimeException('Label is required.');
            }
            $keySource = $labelEn !== '' ? $labelEn : $labelZh;
            $fieldKey = trim($data['field_key'] ?? '');
            if ($fieldKey === '') {
                $fieldKey = generate_attribute_key($pdo, $keySource);
            }
            ensure_member_attribute_tables($pdo);
            $orderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM member_attributes');
            $nextOrder = (int)$orderStmt->fetchColumn();
            $stmt = $pdo->prepare('INSERT INTO member_attributes (field_key, label_zh, label_en, field_type, default_value, sort_order) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$fieldKey, $labelZh !== '' ? $labelZh : $labelEn, $labelEn !== '' ? $labelEn : $labelZh, $fieldType, $default, $nextOrder]);
            $attrId = (int)$pdo->lastInsertId();
            $members = $pdo->query('SELECT id FROM members')->fetchAll(PDO::FETCH_COLUMN);
            if ($members) {
                $insertStmt = $pdo->prepare('INSERT INTO member_attribute_values (member_id, attribute_id, value) VALUES (?,?,?)');
                foreach ($members as $memberId) {
                    $insertStmt->execute([(int)$memberId, $attrId, $default]);
                }
            }
            $attributes = fetch_member_attributes($pdo);
            echo json_encode(['success' => true, 'attributes' => $attributes]);
            break;
        case 'update':
            $id = (int)($data['id'] ?? 0);
            if (!$id) {
                throw new RuntimeException('Invalid attribute id.');
            }
            $labelZh = trim($data['label_zh'] ?? '');
            $labelEn = trim($data['label_en'] ?? '');
            if ($labelZh === '' && $labelEn === '') {
                throw new RuntimeException('Label is required.');
            }
            $default = $data['default_value'] ?? '';
            $fieldType = $data['field_type'] ?? 'text';
            $stmt = $pdo->prepare('UPDATE member_attributes SET label_zh=?, label_en=?, field_type=?, default_value=? WHERE id=?');
            $stmt->execute([$labelZh !== '' ? $labelZh : $labelEn, $labelEn !== '' ? $labelEn : $labelZh, $fieldType, $default, $id]);
            $applyDefault = !empty($data['apply_default']);
            if ($applyDefault) {
                $updateStmt = $pdo->prepare("UPDATE member_attribute_values SET value=? WHERE attribute_id=?");
                $updateStmt->execute([$default, $id]);
            } else {
                $updateStmt = $pdo->prepare("UPDATE member_attribute_values SET value=? WHERE attribute_id=? AND (value IS NULL OR value='')");
                $updateStmt->execute([$default, $id]);
            }
            $attributes = fetch_member_attributes($pdo);
            echo json_encode(['success' => true, 'attributes' => $attributes]);
            break;
        case 'delete':
            $id = (int)($data['id'] ?? 0);
            if (!$id) {
                throw new RuntimeException('Invalid attribute id.');
            }
            $pdo->prepare('DELETE FROM member_attribute_values WHERE attribute_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM member_attributes WHERE id=?')->execute([$id]);
            $attributes = fetch_member_attributes($pdo);
            echo json_encode(['success' => true, 'attributes' => $attributes]);
            break;
        case 'reorder':
            $order = $data['order'] ?? [];
            if (!is_array($order)) {
                $order = [];
            }
            apply_attribute_sort_order($pdo, $order);
            $attributes = fetch_member_attributes($pdo);
            echo json_encode(['success' => true, 'attributes' => $attributes]);
            break;
        default:
            throw new RuntimeException('Unsupported action.');
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
