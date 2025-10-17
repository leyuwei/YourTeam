<?php
require_once __DIR__ . '/config.php';

if (!function_exists('ensure_member_attribute_tables')) {
    function ensure_member_attribute_tables(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS member_attributes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                field_key VARCHAR(191) NOT NULL UNIQUE,
                label_zh VARCHAR(255) NOT NULL,
                label_en VARCHAR(255) NOT NULL,
                field_type VARCHAR(50) NOT NULL DEFAULT 'text',
                default_value TEXT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS member_attribute_values (
                id INT AUTO_INCREMENT PRIMARY KEY,
                member_id INT NOT NULL,
                attribute_id INT NOT NULL,
                value TEXT NULL,
                UNIQUE KEY uniq_member_attribute (member_id, attribute_id)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }
}

if (!function_exists('fetch_member_attributes')) {
    function fetch_member_attributes(PDO $pdo): array
    {
        ensure_member_attribute_tables($pdo);
        $stmt = $pdo->query('SELECT * FROM member_attributes ORDER BY sort_order, id');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('upsert_member_attribute_values')) {
    function upsert_member_attribute_values(PDO $pdo, int $memberId, array $values): void
    {
        ensure_member_attribute_tables($pdo);
        if (empty($values)) {
            return;
        }
        $insertStmt = $pdo->prepare(
            'INSERT INTO member_attribute_values (member_id, attribute_id, value) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        foreach ($values as $attributeId => $value) {
            $insertStmt->execute([$memberId, (int)$attributeId, $value]);
        }
    }
}

if (!function_exists('fetch_member_attribute_map')) {
    function fetch_member_attribute_map(PDO $pdo, array $memberIds): array
    {
        ensure_member_attribute_tables($pdo);
        if (empty($memberIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT member_id, attribute_id, value FROM member_attribute_values WHERE member_id IN ($placeholders)"
        );
        $stmt->execute($memberIds);
        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $memberId = (int)$row['member_id'];
            $attributeId = (int)$row['attribute_id'];
            $map[$memberId][$attributeId] = $row['value'];
        }
        return $map;
    }
}

if (!function_exists('assign_defaults_to_member')) {
    function assign_defaults_to_member(PDO $pdo, int $memberId): void
    {
        $attributes = fetch_member_attributes($pdo);
        if (empty($attributes)) {
            return;
        }
        $existing = fetch_member_attribute_map($pdo, [$memberId]);
        $current = $existing[$memberId] ?? [];
        $values = [];
        foreach ($attributes as $attr) {
            $attrId = (int)$attr['id'];
            if (array_key_exists($attrId, $current)) {
                continue;
            }
            $values[$attrId] = $attr['default_value'];
        }
        if (!empty($values)) {
            upsert_member_attribute_values($pdo, $memberId, $values);
        }
    }
}

if (!function_exists('generate_attribute_key')) {
    function generate_attribute_key(PDO $pdo, string $preferred): string
    {
        $base = preg_replace('/[^a-z0-9_]+/i', '_', strtolower($preferred));
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'attr';
        }
        $candidate = $base;
        $idx = 1;
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM member_attributes WHERE field_key = ?');
        while (true) {
            $stmt->execute([$candidate]);
            if ((int)$stmt->fetchColumn() === 0) {
                return $candidate;
            }
            $candidate = $base . '_' . (++$idx);
        }
    }
}

if (!function_exists('apply_attribute_sort_order')) {
    function apply_attribute_sort_order(PDO $pdo, array $orderedIds): void
    {
        ensure_member_attribute_tables($pdo);
        $stmt = $pdo->prepare('UPDATE member_attributes SET sort_order=? WHERE id=?');
        foreach ($orderedIds as $position => $id) {
            $stmt->execute([$position, (int)$id]);
        }
    }
}
?>
