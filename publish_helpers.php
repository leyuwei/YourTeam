<?php
/**
 * Helper functions for managing publish attributes and values.
 */
function getPublishAttributes(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, sort_order, name_en, name_zh, attribute_type, default_value, options FROM publish_attributes ORDER BY sort_order, id');
    $attributes = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $allowedTypes = ['text', 'textarea', 'file', 'date', 'select'];
    foreach ($attributes as &$attr) {
        $attr['id'] = (int)($attr['id'] ?? 0);
        $attr['sort_order'] = (int)($attr['sort_order'] ?? 0);
        $attr['name_en'] = (string)($attr['name_en'] ?? '');
        $attr['name_zh'] = (string)($attr['name_zh'] ?? '');
        $type = (string)($attr['attribute_type'] ?? 'text');
        $attr['attribute_type'] = in_array($type, $allowedTypes, true) ? $type : 'text';
        $attr['default_value'] = (string)($attr['default_value'] ?? '');
        $attr['options'] = (string)($attr['options'] ?? '');
    }
    unset($attr);
    return $attributes;
}

function getPublishValues(PDO $pdo, array $entryIds): array
{
    $entryIds = array_values(array_filter(array_map('intval', $entryIds), fn($id) => $id > 0));
    if (empty($entryIds)) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($entryIds), '?'));
    $stmt = $pdo->prepare("SELECT entry_id, attribute_id, value FROM publish_values WHERE entry_id IN ($placeholders)");
    $stmt->execute($entryIds);
    $map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $entryId = (int)($row['entry_id'] ?? 0);
        $attributeId = (int)($row['attribute_id'] ?? 0);
        $map[$entryId][$attributeId] = (string)($row['value'] ?? '');
    }
    return $map;
}

function normalizePublishUploads(?array $fileBag): array
{
    if (!is_array($fileBag) || !isset($fileBag['name'])) {
        return [];
    }

    $normalized = [];
    foreach ($fileBag['name'] as $key => $name) {
        $normalized[$key] = [
            'name' => $name,
            'type' => $fileBag['type'][$key] ?? '',
            'tmp_name' => $fileBag['tmp_name'][$key] ?? '',
            'error' => $fileBag['error'][$key] ?? UPLOAD_ERR_NO_FILE,
            'size' => $fileBag['size'][$key] ?? 0,
        ];
    }

    return $normalized;
}

function preparePublishValues(int $entryId, array $attributes, array $postedValues, ?array $uploadedValues, array $existingValues = [], array $clearFlags = []): array
{
    $entryId = (int)$entryId;
    $normalizedUploads = normalizePublishUploads($uploadedValues);
    $result = [];
    $allowedTypes = ['text', 'textarea', 'file', 'date', 'select'];

    foreach ($attributes as $attribute) {
        $attributeId = (int)($attribute['id'] ?? 0);
        if ($attributeId <= 0) {
            continue;
        }

        $attributeType = in_array($attribute['attribute_type'] ?? '', $allowedTypes, true)
            ? $attribute['attribute_type']
            : 'text';
        $defaultValue = $attributeType === 'file' ? '' : (string)($attribute['default_value'] ?? '');
        $currentValue = $existingValues[$attributeId] ?? $defaultValue;
        $value = $postedValues[$attributeId] ?? $currentValue;

        if ($attributeType === 'file') {
            $value = $currentValue;
            $fileInfo = $normalizedUploads[$attributeId] ?? null;
            $isClearing = !empty($clearFlags[$attributeId]);
            $uploadProvided = $entryId > 0 && is_array($fileInfo) && ($fileInfo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

            if ($uploadProvided && ($fileInfo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file((string)($fileInfo['tmp_name'] ?? ''))) {
                $baseDir = __DIR__ . '/publish_uploads/' . $entryId;
                if (!is_dir($baseDir)) {
                    mkdir($baseDir, 0777, true);
                }
                $ext = strtolower(pathinfo((string)($fileInfo['name'] ?? ''), PATHINFO_EXTENSION));
                $safeExt = $ext !== '' ? preg_replace('/[^a-z0-9._-]/i', '', $ext) : '';
                $filename = 'attr_' . $attributeId . '_' . uniqid();
                if ($safeExt !== '') {
                    $filename .= '.' . $safeExt;
                }
                $targetPath = $baseDir . '/' . $filename;
                if (move_uploaded_file((string)$fileInfo['tmp_name'], $targetPath)) {
                    if (!empty($currentValue)) {
                        $oldPath = __DIR__ . '/' . ltrim((string)$currentValue, '/');
                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                    $value = 'publish_uploads/' . $entryId . '/' . $filename;
                    $isClearing = false;
                }
            }

            if ($isClearing) {
                if (!empty($currentValue)) {
                    $oldPath = __DIR__ . '/' . ltrim((string)$currentValue, '/');
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $value = '';
            }
        } elseif (is_array($value)) {
            $value = '';
        }

        $result[$attributeId] = (string)$value;
    }

    return $result;
}

function ensurePublishValues(PDO $pdo, int $entryId, array $submittedValues, array $attributes): void
{
    $entryId = (int)$entryId;
    if ($entryId <= 0) {
        return;
    }
    $allowedTypes = ['text', 'textarea', 'file', 'date', 'select'];
    $insertStmt = $pdo->prepare('INSERT INTO publish_values (entry_id, attribute_id, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
    foreach ($attributes as $attribute) {
        $attributeId = (int)($attribute['id'] ?? 0);
        if ($attributeId <= 0) {
            continue;
        }
        $attributeType = in_array($attribute['attribute_type'] ?? '', $allowedTypes, true) ? $attribute['attribute_type'] : 'text';
        $defaultValue = $attributeType === 'file' ? '' : (string)($attribute['default_value'] ?? '');
        $value = $submittedValues[$attributeId] ?? $defaultValue;
        if ($attributeType === 'select' && ($value === '' || $value === null)) {
            $options = array_values(array_filter(array_map('trim', explode(',', (string)($attribute['options'] ?? '')))));
            if (!empty($options)) {
                $value = $options[0];
            }
        }
        if (is_array($value)) {
            $value = '';
        }
        $insertStmt->execute([$entryId, $attributeId, (string)$value]);
    }
}
