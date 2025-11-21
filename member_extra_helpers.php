<?php
/**
 * Helper functions for managing member extra attributes and values.
 */
function getMemberExtraAttributes(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, sort_order, name_en, name_zh, attribute_type, default_value FROM member_extra_attributes ORDER BY sort_order, id');
    $attributes = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($attributes as &$attr) {
        $attr['id'] = (int)($attr['id'] ?? 0);
        $attr['sort_order'] = (int)($attr['sort_order'] ?? 0);
        $attr['name_en'] = (string)($attr['name_en'] ?? '');
        $attr['name_zh'] = (string)($attr['name_zh'] ?? '');
        $attr['attribute_type'] = in_array($attr['attribute_type'] ?? '', ['text', 'media'], true) ? $attr['attribute_type'] : 'text';
        $attr['default_value'] = (string)($attr['default_value'] ?? '');
    }
    unset($attr);
    return $attributes;
}

function getMemberExtraValues(PDO $pdo, array $memberIds): array
{
    $memberIds = array_values(array_filter(array_map('intval', $memberIds), fn($id) => $id > 0));
    if (empty($memberIds)) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
    $stmt = $pdo->prepare("SELECT member_id, attribute_id, value FROM member_extra_values WHERE member_id IN ($placeholders)");
    $stmt->execute($memberIds);
    $map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $memberId = (int)($row['member_id'] ?? 0);
        $attributeId = (int)($row['attribute_id'] ?? 0);
        $map[$memberId][$attributeId] = (string)($row['value'] ?? '');
    }
    return $map;
}

function ensureMemberExtraValues(PDO $pdo, int $memberId, array $submittedValues, array $attributes): void
{
    $memberId = (int)$memberId;
    if ($memberId <= 0) {
        return;
    }
    $insertStmt = $pdo->prepare('INSERT INTO member_extra_values (member_id, attribute_id, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
    foreach ($attributes as $attribute) {
        $attributeId = (int)($attribute['id'] ?? 0);
        if ($attributeId <= 0) {
            continue;
        }
        $attributeType = in_array($attribute['attribute_type'] ?? '', ['text', 'media'], true) ? $attribute['attribute_type'] : 'text';
        $defaultValue = $attributeType === 'text' ? (string)($attribute['default_value'] ?? '') : '';
        $value = $submittedValues[$attributeId] ?? $defaultValue;
        if (is_array($value)) {
            $value = '';
        }
        $insertStmt->execute([$memberId, $attributeId, (string)$value]);
    }
}
