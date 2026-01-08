<?php
require_once 'config.php';
include_once 'auth.php';
require_once 'publish_helpers.php';

if (($_SESSION['role'] ?? '') !== 'manager') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Permission denied.']);
    exit;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
header('Content-Type: application/json');

if ($method === 'GET') {
    echo json_encode(['success' => true, 'attributes' => getPublishAttributes($pdo)]);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload.']);
    exit;
}

$attributes = $payload['attributes'] ?? [];
if (!is_array($attributes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid attributes payload.']);
    exit;
}

$allowedTypes = ['text', 'textarea', 'file', 'date', 'select'];

try {
    $pdo->beginTransaction();
    $existingStmt = $pdo->query('SELECT id FROM publish_attributes');
    $existingIds = $existingStmt ? array_map('intval', $existingStmt->fetchAll(PDO::FETCH_COLUMN)) : [];
    $seenIds = [];
    $position = 0;

    $updateStmt = $pdo->prepare('UPDATE publish_attributes SET sort_order = ?, name_en = ?, name_zh = ?, attribute_type = ?, default_value = ?, options = ? WHERE id = ?');
    $insertStmt = $pdo->prepare('INSERT INTO publish_attributes (sort_order, name_en, name_zh, attribute_type, default_value, options) VALUES (?, ?, ?, ?, ?, ?)');

    foreach ($attributes as $attribute) {
        if (!is_array($attribute)) {
            continue;
        }
        $id = isset($attribute['id']) ? (int)$attribute['id'] : null;
        $nameEn = trim((string)($attribute['name_en'] ?? ''));
        $nameZh = trim((string)($attribute['name_zh'] ?? ''));
        $attributeType = in_array($attribute['attribute_type'] ?? '', $allowedTypes, true) ? $attribute['attribute_type'] : 'text';
        $defaultValue = $attributeType === 'file' ? '' : (string)($attribute['default_value'] ?? '');
        $options = $attributeType === 'select' ? (string)($attribute['options'] ?? '') : '';

        if ($nameEn === '' && $nameZh === '') {
            throw new RuntimeException('Attribute name cannot be empty.');
        }

        if ($id) {
            $updateStmt->execute([$position, $nameEn, $nameZh, $attributeType, $defaultValue, $options, $id]);
            $seenIds[] = $id;
        } else {
            $insertStmt->execute([$position, $nameEn, $nameZh, $attributeType, $defaultValue, $options]);
            $id = (int)$pdo->lastInsertId();
            $seenIds[] = $id;
            $assignStmt = $pdo->prepare('INSERT INTO publish_values (entry_id, attribute_id, value) SELECT id, ?, ? FROM publish_entries');
            $assignStmt->execute([$id, $defaultValue]);
        }
        $position++;
    }

    $idsToDelete = array_values(array_diff($existingIds, $seenIds));
    if (!empty($idsToDelete)) {
        $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
        $deleteValuesStmt = $pdo->prepare("DELETE FROM publish_values WHERE attribute_id IN ($placeholders)");
        $deleteValuesStmt->execute($idsToDelete);
        $deleteAttrStmt = $pdo->prepare("DELETE FROM publish_attributes WHERE id IN ($placeholders)");
        $deleteAttrStmt->execute($idsToDelete);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'attributes' => getPublishAttributes($pdo)]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
