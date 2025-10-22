<?php
include 'auth.php';

$is_manager = ($_SESSION['role'] ?? '') === 'manager';
$member_id = $_SESSION['member_id'] ?? null;
$username = $_SESSION['username'] ?? '';

function add_asset_log(PDO $pdo, string $targetType, int $targetId, string $operatorName, string $operatorRole, string $action, string $details = ''): void {
    $stmt = $pdo->prepare("INSERT INTO asset_operation_logs (target_type, target_id, operator_name, operator_role, action, details) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$targetType, $targetId, $operatorName, $operatorRole, $action, $details]);
}

function generate_asset_code(PDO $pdo, string $prefix = ''): string {
    $defaultPrefix = 'ASSET-';
    $prefixToUse = $prefix !== '' ? $prefix : $defaultPrefix;
    do {
        $code = $prefixToUse . strtoupper(bin2hex(random_bytes(3)));
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM assets WHERE asset_code = ?');
        $stmt->execute([$code]);
    } while ($stmt->fetchColumn());
    return $code;
}

function handle_asset_image_upload(int $assetId, ?string $currentPath, array $file, array &$errors): ?string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $currentPath;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'assets.messages.image_upload_failed';
        return null;
    }
    $tmpName = $file['tmp_name'];
    $info = @getimagesize($tmpName);
    if ($info === false) {
        $errors[] = 'assets.messages.invalid_image';
        return null;
    }
    $ext = 'jpg';
    if (function_exists('image_type_to_extension')) {
        $ext = ltrim(image_type_to_extension($info[2], false), '.');
    } else {
        $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($originalExt) {
            $ext = $originalExt;
        }
    }
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array(strtolower($ext), $allowed, true)) {
        $ext = 'jpg';
    }
    $baseDir = __DIR__ . '/asset_uploads';
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0777, true);
    }
    $targetDir = $baseDir . '/' . $assetId;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $filename = 'asset_' . $assetId . '_' . uniqid() . '.' . $ext;
    $destination = $targetDir . '/' . $filename;
    if (!move_uploaded_file($tmpName, $destination)) {
        $errors[] = 'assets.messages.image_upload_failed';
        return null;
    }
    if ($currentPath) {
        $oldPath = __DIR__ . '/' . $currentPath;
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
        $oldDir = dirname($oldPath);
        if (is_dir($oldDir) && count(array_diff(scandir($oldDir), ['.', '..'])) === 0) {
            @rmdir($oldDir);
        }
    }
    $relative = 'asset_uploads/' . $assetId . '/' . $filename;
    return $relative;
}

function remove_asset_files(array $paths): void {
    foreach ($paths as $path) {
        if (!$path) continue;
        $absolute = __DIR__ . '/' . $path;
        if (is_file($absolute)) {
            @unlink($absolute);
            $dir = dirname($absolute);
            if (is_dir($dir) && count(array_diff(scandir($dir), ['.', '..'])) === 0) {
                @rmdir($dir);
            }
        }
    }
}

function normalize_asset_suffix(string $assetCode, string $prefix): string {
    $suffix = $assetCode;
    if ($prefix !== '' && strncmp($assetCode, $prefix, strlen($prefix)) === 0) {
        $suffix = substr($assetCode, strlen($prefix));
    }
    $suffix = trim((string)$suffix);
    if ($suffix !== '' && preg_match('/^\d+$/', $suffix)) {
        $normalizedNumeric = ltrim($suffix, '0');
        $suffix = $normalizedNumeric === '' ? '0' : $normalizedNumeric;
    }
    return $suffix;
}

function flatten_json_keys($data, string $prefix = ''): array {
    $results = [];
    if (is_array($data)) {
        $isAssoc = array_keys($data) !== range(0, count($data) - 1);
        foreach ($data as $key => $value) {
            $segment = (string)$key;
            $nextPrefix = $isAssoc
                ? ($prefix === '' ? $segment : $prefix . '.' . $segment)
                : ($prefix === '' ? '[' . $segment . ']' : $prefix . '[' . $segment . ']');
            if (is_array($value)) {
                $results = array_merge($results, flatten_json_keys($value, $nextPrefix));
            } else {
                $results[] = $nextPrefix;
            }
        }
    } elseif ($prefix !== '') {
        $results[] = $prefix;
    }
    return array_values(array_unique($results));
}

function fetch_remote_payload(string $url, int $timeout = 5): array {
    $error = null;
    $body = null;
    $status = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch) ?: 'curl_error';
        }
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE) ?: null;
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeout,
                'ignore_errors' => true
            ]
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            $lastError = error_get_last();
            $error = $lastError['message'] ?? 'stream_error';
        }
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $line) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $matches)) {
                    $status = (int)$matches[1];
                    break;
                }
            }
        }
    }
    return [$body, $error, $status];
}

function asset_sync_json(array $payload): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$assetCodePrefix = 'ASSET-';
$assetLinkPrefix = '';
$assetSyncApiPrefix = '';
$assetSyncMapping = [];
$assetSyncMappingJson = null;
$settingsRow = false;
$tableMissing = false;
$columnMissing = false;
try {
    $settingsStmt = $pdo->query('SELECT code_prefix, link_prefix, sync_api_prefix, sync_mapping FROM asset_settings WHERE id=1');
    $settingsRow = $settingsStmt->fetch();
} catch (PDOException $e) {
    if ($e->getCode() === '42S22') {
        $columnMissing = true;
        try {
            $fallbackStmt = $pdo->query('SELECT code_prefix, link_prefix FROM asset_settings WHERE id=1');
            $settingsRow = $fallbackStmt->fetch();
        } catch (PDOException $inner) {
            if ($inner->getCode() === '42S02') {
                $tableMissing = true;
            } else {
                throw $inner;
            }
        }
    } elseif ($e->getCode() === '42S02') {
        $tableMissing = true;
    } else {
        throw $e;
    }
}
if ($settingsRow && array_key_exists('code_prefix', $settingsRow)) {
    $retrievedPrefix = trim((string)$settingsRow['code_prefix']);
    if ($retrievedPrefix !== '') {
        $assetCodePrefix = $retrievedPrefix;
    }
}
if ($settingsRow && array_key_exists('link_prefix', $settingsRow)) {
    $retrievedLink = str_replace(["\r", "\n"], '', (string)$settingsRow['link_prefix']);
    $retrievedLink = trim($retrievedLink);
    if ($retrievedLink !== '') {
        $assetLinkPrefix = $retrievedLink;
    }
}
if ($settingsRow && array_key_exists('sync_api_prefix', $settingsRow)) {
    $retrievedSync = str_replace(["\r", "\n"], '', (string)$settingsRow['sync_api_prefix']);
    $retrievedSync = trim($retrievedSync);
    if ($retrievedSync !== '') {
        $assetSyncApiPrefix = $retrievedSync;
    }
}
if ($settingsRow && array_key_exists('sync_mapping', $settingsRow)) {
    $rawMapping = $settingsRow['sync_mapping'];
    if (is_string($rawMapping) && $rawMapping !== '') {
        $decodedMapping = json_decode($rawMapping, true);
        if (is_array($decodedMapping)) {
            $assetSyncMapping = $decodedMapping;
            $assetSyncMappingJson = json_encode($decodedMapping, JSON_UNESCAPED_UNICODE);
        } else {
            $assetSyncMappingJson = $rawMapping;
        }
    } elseif ($rawMapping === null) {
        $assetSyncMappingJson = null;
    }
}
if (!$settingsRow && !$tableMissing && !$columnMissing) {
    try {
        $ensureStmt = $pdo->prepare('INSERT INTO asset_settings (id, code_prefix, link_prefix, sync_api_prefix, sync_mapping) VALUES (1, ?, ?, ?, NULL) ON DUPLICATE KEY UPDATE code_prefix = code_prefix, link_prefix = link_prefix, sync_api_prefix = sync_api_prefix, sync_mapping = sync_mapping');
        $ensureStmt->execute([$assetCodePrefix, $assetLinkPrefix, $assetSyncApiPrefix]);
    } catch (PDOException $e) {
        if ($e->getCode() !== '42S02' && $e->getCode() !== '42S22') {
            throw $e;
        }
    }
}

$syncAttributeOptions = [
    'asset_code_suffix' => ['key' => 'assets.sync.attributes.asset_code', 'default' => 'Asset Code Suffix'],
    'category' => ['key' => 'assets.sync.attributes.category', 'default' => 'Category'],
    'model' => ['key' => 'assets.sync.attributes.model', 'default' => 'Model / Configuration'],
    'organization' => ['key' => 'assets.sync.attributes.organization', 'default' => 'Owning Unit'],
    'remarks' => ['key' => 'assets.sync.attributes.remarks', 'default' => 'Remarks'],
    'status' => ['key' => 'assets.sync.attributes.status', 'default' => 'Status'],
    'owner_name' => ['key' => 'assets.sync.attributes.owner', 'default' => 'Responsible Person'],
    'office_label' => ['key' => 'assets.sync.attributes.office', 'default' => 'Office Label'],
    'seat_label' => ['key' => 'assets.sync.attributes.seat', 'default' => 'Workstation Label'],
    'image_url' => ['key' => 'assets.sync.attributes.image', 'default' => 'Image URL']
];

if (isset($_GET['asset_logs'])) {
    $assetId = (int)$_GET['asset_logs'];
    $stmt = $pdo->prepare('SELECT owner_member_id FROM assets WHERE id=?');
    $stmt->execute([$assetId]);
    $assetOwner = $stmt->fetchColumn();
    if ($assetOwner === false) {
        http_response_code(404);
        echo json_encode([]);
        exit;
    }
    if (!$is_manager) {
        if ($assetOwner === null || (int)$assetOwner !== (int)$member_id) {
            http_response_code(403);
            echo json_encode([]);
            exit;
        }
    }
    $stmt = $pdo->prepare('SELECT action, details, operator_name, operator_role, created_at FROM asset_operation_logs WHERE target_type="asset" AND target_id=? ORDER BY created_at DESC');
    $stmt->execute([$assetId]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'fetch_sync_preview') {
        if (!$is_manager) {
            asset_sync_json(['success' => false, 'message' => 'assets.messages.permission_denied']);
        }
        try {
            if ($assetSyncApiPrefix === '') {
                throw new RuntimeException('assets.sync.errors.prefix_missing');
            }
            $assetInput = isset($_POST['asset_id']) ? trim((string)$_POST['asset_id']) : '';
            if ($assetInput === '') {
                throw new RuntimeException('assets.sync.errors.asset_required');
            }
            $suffix = normalize_asset_suffix($assetInput, $assetCodePrefix);
            if ($suffix === '') {
                throw new RuntimeException('assets.sync.errors.asset_suffix_empty');
            }
            $url = $assetSyncApiPrefix . $suffix;
            [$body, $fetchError, $statusCode] = fetch_remote_payload($url);
            if ($fetchError !== null) {
                throw new RuntimeException('assets.sync.errors.fetch_failed');
            }
            if ($statusCode !== null && $statusCode >= 400) {
                throw new RuntimeException('assets.sync.errors.http');
            }
            if (!is_string($body) || trim($body) === '') {
                throw new RuntimeException('assets.sync.errors.fetch_failed');
            }
            $decoded = json_decode($body, true);
            if (!is_array($decoded)) {
                throw new RuntimeException('assets.sync.errors.invalid_json');
            }
            $flatKeys = flatten_json_keys($decoded);
            sort($flatKeys, SORT_NATURAL | SORT_FLAG_CASE);
            $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($pretty === false) {
                $pretty = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
            if ($pretty === false) {
                $pretty = $body;
            }
            asset_sync_json([
                'success' => true,
                'message' => 'assets.sync.status.loaded',
                'url' => $url,
                'raw' => $pretty,
                'keys' => $flatKeys,
                'mapping' => $assetSyncMapping,
                'status' => $statusCode
            ]);
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'assets.') !== 0) {
                $msg = 'assets.sync.errors.unknown';
            }
            asset_sync_json(['success' => false, 'message' => $msg]);
        }
    }
    if ($action === 'save_sync_mapping') {
        if (!$is_manager) {
            asset_sync_json(['success' => false, 'message' => 'assets.messages.permission_denied']);
        }
        try {
            $mappingInput = $_POST['mapping'] ?? '';
            $decoded = [];
            if (is_string($mappingInput) && $mappingInput !== '') {
                $decoded = json_decode($mappingInput, true);
                if (!is_array($decoded)) {
                    throw new RuntimeException('assets.sync.errors.invalid_json');
                }
            }
            $cleanMapping = [];
            foreach ($syncAttributeOptions as $attribute => $meta) {
                if (!isset($decoded[$attribute])) {
                    continue;
                }
                $value = $decoded[$attribute];
                if (!is_string($value)) {
                    continue;
                }
                $value = trim($value);
                if ($value === '') {
                    continue;
                }
                if (function_exists('mb_substr')) {
                    $value = mb_substr($value, 0, 255);
                } else {
                    $value = substr($value, 0, 255);
                }
                $cleanMapping[$attribute] = $value;
            }
            $encoded = json_encode($cleanMapping, JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                throw new RuntimeException('assets.sync.errors.unknown');
            }
            $stmt = $pdo->prepare('INSERT INTO asset_settings (id, code_prefix, link_prefix, sync_api_prefix, sync_mapping) VALUES (1, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE sync_mapping = VALUES(sync_mapping), updated_at = CURRENT_TIMESTAMP');
            $stmt->execute([$assetCodePrefix, $assetLinkPrefix, $assetSyncApiPrefix, $encoded]);
            $assetSyncMapping = $cleanMapping;
            $assetSyncMappingJson = $encoded;
            asset_sync_json(['success' => true, 'message' => 'assets.sync.status.saved', 'mapping' => $cleanMapping]);
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'assets.') !== 0) {
                $msg = 'assets.sync.errors.unknown';
            }
            asset_sync_json(['success' => false, 'message' => $msg]);
        }
    }
    $errors = [];
    try {
        if ($action === 'save_settings') {
            if (!$is_manager) {
                throw new RuntimeException('assets.messages.permission_denied');
            }
            $prefixInput = isset($_POST['code_prefix']) ? trim((string)$_POST['code_prefix']) : '';
            $prefixInput = str_replace(["\r", "\n"], '', $prefixInput);
            if ($prefixInput === '') {
                $prefixInput = 'ASSET-';
            }
            $maxLen = 30;
            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                if (mb_strlen($prefixInput) > $maxLen) {
                    $prefixInput = mb_substr($prefixInput, 0, $maxLen);
                }
            } else {
                if (strlen($prefixInput) > $maxLen) {
                    $prefixInput = substr($prefixInput, 0, $maxLen);
                }
            }
            $linkPrefixInput = isset($_POST['link_prefix']) ? trim((string)$_POST['link_prefix']) : '';
            $linkPrefixInput = str_replace(["\r", "\n"], '', $linkPrefixInput);
            $maxLinkLen = 255;
            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                if (mb_strlen($linkPrefixInput) > $maxLinkLen) {
                    $linkPrefixInput = mb_substr($linkPrefixInput, 0, $maxLinkLen);
                }
            } else {
                if (strlen($linkPrefixInput) > $maxLinkLen) {
                    $linkPrefixInput = substr($linkPrefixInput, 0, $maxLinkLen);
                }
            }
            $syncPrefixInput = isset($_POST['sync_api_prefix']) ? trim((string)$_POST['sync_api_prefix']) : '';
            $syncPrefixInput = str_replace(["\r", "\n"], '', $syncPrefixInput);
            $maxSyncLen = 255;
            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                if (mb_strlen($syncPrefixInput) > $maxSyncLen) {
                    $syncPrefixInput = mb_substr($syncPrefixInput, 0, $maxSyncLen);
                }
            } else {
                if (strlen($syncPrefixInput) > $maxSyncLen) {
                    $syncPrefixInput = substr($syncPrefixInput, 0, $maxSyncLen);
                }
            }
            $stmt = $pdo->prepare('INSERT INTO asset_settings (id, code_prefix, link_prefix, sync_api_prefix, sync_mapping) VALUES (1, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE code_prefix = VALUES(code_prefix), link_prefix = VALUES(link_prefix), sync_api_prefix = VALUES(sync_api_prefix), updated_at = CURRENT_TIMESTAMP');
            $stmt->execute([$prefixInput, $linkPrefixInput, $syncPrefixInput, $assetSyncMappingJson]);
            $assetCodePrefix = $prefixInput;
            $assetLinkPrefix = $linkPrefixInput;
            $assetSyncApiPrefix = $syncPrefixInput;
            $_SESSION['asset_flash'] = ['type' => 'success', 'key' => 'assets.messages.settings_saved', 'default' => 'Settings updated successfully'];
        } elseif ($action === 'save_inbound') {
            if (!$is_manager) {
                throw new RuntimeException('assets.messages.permission_denied');
            }
            $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
            $orderNumber = trim($_POST['order_number'] ?? '');
            $supplier = trim($_POST['supplier'] ?? '');
            $supplierLead = trim($_POST['supplier_lead'] ?? '');
            $receiverLead = trim($_POST['receiver_lead'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $dateInput = $_POST['inbound_date'] ?? null;
            $date = ($dateInput === '' ? null : $dateInput);
            $notes = trim($_POST['notes'] ?? '');
            if ($orderNumber === '') {
                throw new RuntimeException('assets.messages.order_required');
            }
            $stmt = $pdo->prepare('SELECT id FROM asset_inbound_orders WHERE order_number=?' . ($id ? ' AND id<>?' : ''));
            if ($id) {
                $stmt->execute([$orderNumber, $id]);
            } else {
                $stmt->execute([$orderNumber]);
            }
            if ($stmt->fetch()) {
                throw new RuntimeException('assets.messages.order_exists');
            }
            if ($id) {
                $stmt = $pdo->prepare('SELECT * FROM asset_inbound_orders WHERE id=?');
                $stmt->execute([$id]);
                $old = $stmt->fetch();
                if (!$old) {
                    throw new RuntimeException('assets.messages.inbound_missing');
                }
                $stmt = $pdo->prepare('UPDATE asset_inbound_orders SET order_number=?, supplier=?, supplier_lead=?, receiver_lead=?, arrival_location=?, arrival_date=?, notes=? WHERE id=?');
                $stmt->execute([$orderNumber, $supplier, $supplierLead, $receiverLead, $location, $date, $notes, $id]);
                $changes = [];
                if ($old['order_number'] !== $orderNumber) $changes[] = 'Order #' . $old['order_number'] . ' → ' . $orderNumber;
                if ($old['supplier'] !== $supplier) $changes[] = 'Supplier changed';
                if ($old['supplier_lead'] !== $supplierLead) $changes[] = 'Supplier lead updated';
                if ($old['receiver_lead'] !== $receiverLead) $changes[] = 'Receiver lead updated';
                if ($old['arrival_location'] !== $location) $changes[] = 'Location updated';
                if ($old['arrival_date'] !== $date) $changes[] = 'Date updated';
                if ($old['notes'] !== $notes) $changes[] = 'Notes updated';
                add_asset_log($pdo, 'inbound_order', $id, $username, $_SESSION['role'], 'Inbound updated', implode('; ', $changes));
                $_SESSION['asset_flash'] = ['type' => 'success', 'key' => 'assets.messages.inbound_updated', 'default' => 'Inbound order updated successfully'];
            } else {
                $stmt = $pdo->prepare('INSERT INTO asset_inbound_orders (order_number, supplier, supplier_lead, receiver_lead, arrival_location, arrival_date, notes) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$orderNumber, $supplier, $supplierLead, $receiverLead, $location, $date, $notes]);
                $newId = (int)$pdo->lastInsertId();
                add_asset_log($pdo, 'inbound_order', $newId, $username, $_SESSION['role'], 'Inbound created', 'Order #' . $orderNumber);
                $_SESSION['asset_flash'] = ['type' => 'success', 'key' => 'assets.messages.inbound_created', 'default' => 'Inbound order created successfully'];
            }
        } elseif ($action === 'delete_inbound') {
            if (!$is_manager) {
                throw new RuntimeException('assets.messages.permission_denied');
            }
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM asset_inbound_orders WHERE id=?');
            $stmt->execute([$id]);
            $order = $stmt->fetch();
            if (!$order) {
                throw new RuntimeException('assets.messages.inbound_missing');
            }
            $assetsStmt = $pdo->prepare('SELECT id, image_path, asset_code FROM assets WHERE inbound_order_id=?');
            $assetsStmt->execute([$id]);
            $assetsUnderOrder = $assetsStmt->fetchAll();
            $paths = array_column($assetsUnderOrder, 'image_path');
            $pdo->beginTransaction();
            $delStmt = $pdo->prepare('DELETE FROM asset_inbound_orders WHERE id=?');
            $delStmt->execute([$id]);
            add_asset_log($pdo, 'inbound_order', $id, $username, $_SESSION['role'], 'Inbound deleted', 'Order #' . $order['order_number'] . ' removed with ' . count($assetsUnderOrder) . ' assets');
            foreach ($assetsUnderOrder as $assetRow) {
                add_asset_log($pdo, 'asset', (int)$assetRow['id'], $username, $_SESSION['role'], 'Asset removed with inbound', 'Asset #' . $assetRow['asset_code']);
            }
            $pdo->commit();
            remove_asset_files($paths);
            $_SESSION['asset_flash'] = ['type' => 'success', 'key' => 'assets.messages.inbound_deleted', 'default' => 'Inbound order deleted successfully'];
        } elseif ($action === 'save_asset') {
            $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
            $inboundId = isset($_POST['inbound_order_id']) ? (int)$_POST['inbound_order_id'] : 0;
            $category = trim($_POST['category'] ?? '');
            $model = trim($_POST['model'] ?? '');
            $organization = trim($_POST['organization'] ?? '');
            $remarksInput = trim($_POST['remarks'] ?? '');
            $remarksValue = $remarksInput === '' ? null : $remarksInput;
            $officeId = isset($_POST['office_id']) && $_POST['office_id'] !== '' ? (int)$_POST['office_id'] : null;
            $seatId = isset($_POST['seat_id']) && $_POST['seat_id'] !== '' ? (int)$_POST['seat_id'] : null;
            $ownerSelection = isset($_POST['owner_id']) ? trim((string)$_POST['owner_id']) : '';
            $ownerExternalInput = isset($_POST['owner_external_name']) ? trim((string)$_POST['owner_external_name']) : '';
            $ownerId = null;
            $ownerExternalName = null;
            if ($ownerSelection === '__external__') {
                if (function_exists('mb_substr')) {
                    $ownerExternalName = mb_substr($ownerExternalInput, 0, 150);
                } else {
                    $ownerExternalName = substr($ownerExternalInput, 0, 150);
                }
                $ownerExternalName = str_replace(["\r", "\n"], '', (string)$ownerExternalName);
                $ownerExternalName = trim($ownerExternalName);
                if ($ownerExternalName === '') {
                    throw new RuntimeException('assets.messages.owner_external_required');
                }
            } else {
                if ($ownerSelection !== '') {
                    $ownerId = (int)$ownerSelection;
                    if ($ownerId <= 0) {
                        $ownerId = null;
                    }
                }
                $ownerExternalName = null;
            }
            $status = $_POST['status'] ?? 'pending';
            $allowedStatus = ['in_use', 'maintenance', 'pending', 'lost', 'retired'];
            if (!in_array($status, $allowedStatus, true)) {
                $status = 'pending';
            }
            $suffixInput = trim((string)($_POST['asset_code_suffix'] ?? ''));
            $usePrefix = $assetCodePrefix !== '' && ($_POST['asset_code_use_prefix'] ?? '1') === '1';
            $existing = null;
            if ($id) {
                $stmt = $pdo->prepare('SELECT * FROM assets WHERE id=?');
                $stmt->execute([$id]);
                $existing = $stmt->fetch();
                if (!$existing) {
                    throw new RuntimeException('assets.messages.asset_missing');
                }
                if (!$is_manager && (int)$existing['owner_member_id'] !== (int)$member_id) {
                    throw new RuntimeException('assets.messages.permission_denied');
                }
            }
            if ($existing && !$is_manager) {
                $inboundId = (int)$existing['inbound_order_id'];
                $category = $existing['category'];
                $model = $existing['model'];
            }
            if ($inboundId <= 0) {
                throw new RuntimeException('assets.messages.inbound_missing');
            }
            $stmt = $pdo->prepare('SELECT id FROM asset_inbound_orders WHERE id=?');
            $stmt->execute([$inboundId]);
            if (!$stmt->fetch()) {
                throw new RuntimeException('assets.messages.inbound_missing');
            }
            if ($seatId !== null) {
                $seatStmt = $pdo->prepare('SELECT office_id FROM office_seats WHERE id=?');
                $seatStmt->execute([$seatId]);
                $seat = $seatStmt->fetch();
                if (!$seat) {
                    throw new RuntimeException('assets.messages.invalid_seat');
                }
                if ($officeId !== null && (int)$seat['office_id'] !== $officeId) {
                    throw new RuntimeException('assets.messages.seat_office_mismatch');
                }
                if ($officeId === null) {
                    $officeId = (int)$seat['office_id'];
                }
            }
            if ($ownerId !== null) {
                $ownStmt = $pdo->prepare('SELECT id FROM members WHERE id=?');
                $ownStmt->execute([$ownerId]);
                if (!$ownStmt->fetch()) {
                    $ownerId = null;
                }
            }
            if ($id) {
                $assetCode = $usePrefix
                    ? ($suffixInput === '' ? '' : $assetCodePrefix . $suffixInput)
                    : $suffixInput;
                if ($assetCode === '') {
                    $assetCode = generate_asset_code($pdo, $assetCodePrefix);
                }
                if (!$is_manager) {
                    $assetCode = $existing['asset_code'];
                }
                if ($assetCode !== $existing['asset_code']) {
                    $chk = $pdo->prepare('SELECT COUNT(*) FROM assets WHERE asset_code=? AND id<>?');
                    $chk->execute([$assetCode, $id]);
                    if ($chk->fetchColumn()) {
                        throw new RuntimeException('assets.messages.asset_code_exists');
                    }
                }
                if (!$is_manager) {
                    $inboundId = (int)$existing['inbound_order_id'];
                    $category = $existing['category'];
                    $model = $existing['model'];
                }
                $update = $pdo->prepare('UPDATE assets SET inbound_order_id=?, asset_code=?, category=?, model=?, organization=?, remarks=?, current_office_id=?, current_seat_id=?, owner_member_id=?, owner_external_name=?, status=?, updated_at=NOW() WHERE id=?');
                $update->execute([$inboundId, $assetCode, $category, $model, $organization, $remarksValue, $officeId, $seatId, $ownerId, $ownerExternalName, $status, $id]);
                $newPath = handle_asset_image_upload($id, $existing['image_path'], $_FILES['image'] ?? [], $errors);
                if ($errors) {
                    throw new RuntimeException($errors[0]);
                }
                if ($newPath !== $existing['image_path']) {
                    $updImg = $pdo->prepare('UPDATE assets SET image_path=?, updated_at=NOW() WHERE id=?');
                    $updImg->execute([$newPath, $id]);
                }
                $changes = [];
                if ($existing['asset_code'] !== $assetCode) $changes[] = 'Code ' . $existing['asset_code'] . ' → ' . $assetCode;
                if ($existing['category'] !== $category) $changes[] = 'Category updated';
                if ($existing['model'] !== $model) $changes[] = 'Model updated';
                if ($existing['organization'] !== $organization) $changes[] = 'Organization updated';
                $existingRemarks = $existing['remarks'] ?? null;
                if ($existingRemarks !== $remarksValue) $changes[] = 'Remarks updated';
                if ((int)$existing['current_office_id'] !== (int)$officeId) $changes[] = 'Office updated';
                if ((int)$existing['current_seat_id'] !== (int)$seatId) $changes[] = 'Seat updated';
                $existingExternal = is_string($existing['owner_external_name'] ?? null) ? trim($existing['owner_external_name']) : '';
                $newExternal = is_string($ownerExternalName) ? trim($ownerExternalName) : '';
                if ((int)$existing['owner_member_id'] !== (int)$ownerId || $existingExternal !== $newExternal) $changes[] = 'Owner updated';
                if ($existing['status'] !== $status) $changes[] = 'Status: ' . $existing['status'] . ' → ' . $status;
                if ($newPath !== $existing['image_path']) $changes[] = 'Image replaced';
                add_asset_log($pdo, 'asset', $id, $username, $_SESSION['role'], 'Asset updated', implode('; ', $changes));
                $_SESSION['asset_flash'] = ['type' => 'success', 'key' => 'assets.messages.asset_updated', 'default' => 'Asset updated successfully'];
            } else {
                if (!$is_manager) {
                    throw new RuntimeException('assets.messages.permission_denied');
                }
                $assetCode = $usePrefix
                    ? ($suffixInput === '' ? '' : $assetCodePrefix . $suffixInput)
                    : $suffixInput;
                if ($assetCode === '') {
                    $assetCode = generate_asset_code($pdo, $assetCodePrefix);
                } else {
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM assets WHERE asset_code=?');
                    $stmt->execute([$assetCode]);
                    if ($stmt->fetchColumn()) {
                        throw new RuntimeException('assets.messages.asset_code_exists');
                    }
                }
                $insert = $pdo->prepare('INSERT INTO assets (inbound_order_id, asset_code, category, model, organization, remarks, current_office_id, current_seat_id, owner_member_id, owner_external_name, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                $insert->execute([$inboundId, $assetCode, $category, $model, $organization, $remarksValue, $officeId, $seatId, $ownerId, $ownerExternalName, $status]);
                $newId = (int)$pdo->lastInsertId();
                $newPath = handle_asset_image_upload($newId, null, $_FILES['image'] ?? [], $errors);
                if ($errors) {
                    $pdo->prepare('DELETE FROM assets WHERE id=?')->execute([$newId]);
                    throw new RuntimeException($errors[0]);
                }
                if ($newPath) {
                    $pdo->prepare('UPDATE assets SET image_path=? WHERE id=?')->execute([$newPath, $newId]);
                }
                add_asset_log($pdo, 'asset', $newId, $username, $_SESSION['role'], 'Asset created', 'Code ' . $assetCode);
                $_SESSION['asset_flash'] = ['type' => 'success', 'key' => 'assets.messages.asset_created', 'default' => 'Asset created successfully'];
            }
        } elseif ($action === 'delete_asset') {
            if (!$is_manager) {
                throw new RuntimeException('assets.messages.permission_denied');
            }
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM assets WHERE id=?');
            $stmt->execute([$id]);
            $asset = $stmt->fetch();
            if (!$asset) {
                throw new RuntimeException('assets.messages.asset_missing');
            }
            $pdo->prepare('DELETE FROM assets WHERE id=?')->execute([$id]);
            remove_asset_files([$asset['image_path']]);
            add_asset_log($pdo, 'asset', $id, $username, $_SESSION['role'], 'Asset deleted', 'Code ' . $asset['asset_code']);
            $_SESSION['asset_flash'] = ['type' => 'success', 'key' => 'assets.messages.asset_deleted', 'default' => 'Asset deleted successfully'];
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $key = $e->getMessage();
        if (strpos($key, 'assets.messages.') !== 0) {
            $key = 'assets.messages.generic_error';
        }
        $_SESSION['asset_flash'] = ['type' => 'danger', 'key' => $key, 'default' => 'Operation failed'];
    }
    header('Location: assets.php');
    exit;
}

$flash = $_SESSION['asset_flash'] ?? null;
unset($_SESSION['asset_flash']);

$inboundOrders = [];
if ($is_manager) {
    $inboundTableStmt = $pdo->query('SELECT io.*, COUNT(a.id) AS asset_count FROM asset_inbound_orders io LEFT JOIN assets a ON a.inbound_order_id = io.id GROUP BY io.id ORDER BY io.arrival_date DESC, io.id DESC');
    $inboundOrders = $inboundTableStmt->fetchAll();
}

$assetQuery = 'SELECT a.*, io.order_number, io.arrival_date, m.name AS owner_name, o.name AS office_name, s.label AS seat_label FROM assets a JOIN asset_inbound_orders io ON a.inbound_order_id=io.id LEFT JOIN members m ON a.owner_member_id=m.id LEFT JOIN offices o ON a.current_office_id=o.id LEFT JOIN office_seats s ON a.current_seat_id=s.id';
$params = [];
if (!$is_manager && $member_id) {
    $assetQuery .= ' WHERE a.owner_member_id = ?';
    $params[] = $member_id;
}
$assetQuery .= ' ORDER BY io.arrival_date DESC, a.id DESC';
$stmt = $pdo->prepare($assetQuery);
$stmt->execute($params);
$assets = $stmt->fetchAll();

$categoryStats = [];
$statusStats = [];
if ($is_manager) {
    $categoryStmt = $pdo->prepare('SELECT category, COUNT(*) AS total FROM assets GROUP BY category ORDER BY total DESC');
    $categoryStmt->execute();
    $categoryStats = $categoryStmt->fetchAll();

    $statusStmt = $pdo->prepare('SELECT status, COUNT(*) AS total FROM assets GROUP BY status');
    $statusStmt->execute();
    $statusStats = $statusStmt->fetchAll();
}

$inboundOptions = $is_manager
    ? $pdo->query('SELECT id, order_number FROM asset_inbound_orders ORDER BY arrival_date DESC, id DESC')->fetchAll()
    : [];
$members = $pdo->query('SELECT id, name FROM members ORDER BY name')->fetchAll();
$offices = $pdo->query('SELECT id, name, location_description, region FROM offices ORDER BY name')->fetchAll();
$seats = $pdo->query('SELECT id, office_id, label FROM office_seats ORDER BY label')->fetchAll();
$memberAssets = [];
if ($is_manager) {
    $memberAssetStmt = $pdo->query('SELECT m.id AS member_id, m.name AS member_name, a.id AS asset_id, a.asset_code, a.category, a.model, a.organization, a.status, a.updated_at, o.name AS office_name, s.label AS seat_label FROM members m LEFT JOIN assets a ON a.owner_member_id = m.id LEFT JOIN offices o ON a.current_office_id = o.id LEFT JOIN office_seats s ON a.current_seat_id = s.id WHERE m.status = "in_work" ORDER BY m.name ASC, a.asset_code ASC, a.id ASC');
    foreach ($memberAssetStmt->fetchAll() as $row) {
        $memberId = (int)$row['member_id'];
        if (!isset($memberAssets[$memberId])) {
            $memberAssets[$memberId] = [
                'member_name' => $row['member_name'],
                'assets' => []
            ];
        }
        if (!empty($row['asset_id'])) {
            $memberAssets[$memberId]['assets'][] = [
                'asset_code' => $row['asset_code'],
                'organization' => $row['organization'],
                'category' => $row['category'],
                'model' => $row['model'],
                'office_name' => $row['office_name'],
                'seat_label' => $row['seat_label'],
                'status' => $row['status'],
                'updated_at' => $row['updated_at']
            ];
        }
    }
}

include 'header.php';
?>
<div class="mb-4">
  <h2 data-i18n="assets.title">Assets</h2>
  <?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type']); ?>" data-i18n="<?= htmlspecialchars($flash['key']); ?>"><?= htmlspecialchars($flash['default']); ?></div>
  <?php endif; ?>
</div>
<?php if ($is_manager): ?>
<div class="d-flex justify-content-end mb-4">
  <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assetSettingsModal" data-i18n="assets.settings.open_modal">Manage General Settings</button>
</div>
<?php endif; ?>
<?php if ($is_manager): ?>
<div class="asset-stats mb-4">
  <div class="row g-3">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title" data-i18n="assets.stats.by_category">By Category</h5>
          <div class="d-flex flex-wrap gap-3" id="assetCategoryStats">
            <?php if ($categoryStats): ?>
              <?php foreach ($categoryStats as $row): ?>
              <div class="stats-chip">
                <div class="stats-label"><?= htmlspecialchars($row['category'] ?: '-'); ?></div>
                <div class="stats-value"><?= (int)$row['total']; ?></div>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-muted" data-i18n="assets.stats.none">No data</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title" data-i18n="assets.stats.by_status">By Status</h5>
          <div class="d-flex flex-wrap gap-3" id="assetStatusStats">
            <?php if ($statusStats): ?>
              <?php foreach ($statusStats as $row): ?>
              <div class="stats-chip">
                <div class="stats-label" data-i18n="assets.status.<?= htmlspecialchars($row['status']); ?>"><?= htmlspecialchars($row['status']); ?></div>
                <div class="stats-value"><?= (int)$row['total']; ?></div>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-muted" data-i18n="assets.stats.none">No data</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="mb-0" data-i18n="assets.inbound.title">Inbound Orders</h3>
    <?php if ($is_manager): ?>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#inboundModal" data-mode="create" data-i18n="assets.inbound.add">New Inbound</button>
    <?php endif; ?>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-bordered align-middle mb-0 asset-table-nowrap">
        <thead class="table-light">
          <tr>
            <th data-i18n="assets.inbound.order_number">Order #</th>
            <th data-i18n="assets.inbound.supplier">Supplier</th>
            <th data-i18n="assets.inbound.supplier_lead">Supplier Lead</th>
            <th data-i18n="assets.inbound.receiver_lead">Receiver Lead</th>
            <th data-i18n="assets.inbound.location">Location</th>
            <th data-i18n="assets.inbound.date">Inbound Date</th>
            <th data-i18n="assets.inbound.notes">Notes</th>
            <th data-i18n="assets.inbound.assets_count">Assets</th>
            <?php if ($is_manager): ?><th data-i18n="assets.table.actions">Actions</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if ($inboundOrders): ?>
            <?php foreach ($inboundOrders as $order): ?>
            <tr data-order-id="<?= (int)$order['id']; ?>">
              <td><?= htmlspecialchars($order['order_number']); ?></td>
              <td><?= htmlspecialchars($order['supplier']); ?></td>
              <td><?= htmlspecialchars($order['supplier_lead']); ?></td>
              <td><?= htmlspecialchars($order['receiver_lead']); ?></td>
              <td><?= htmlspecialchars($order['arrival_location']); ?></td>
              <td><?= htmlspecialchars($order['arrival_date']); ?></td>
              <td><?= htmlspecialchars($order['notes']); ?></td>
              <td><?= (int)$order['asset_count']; ?></td>
              <?php if ($is_manager): ?>
              <td>
                <button class="btn btn-sm btn-primary inbound-edit" data-bs-toggle="modal" data-bs-target="#inboundModal" data-mode="edit" data-order='<?= json_encode($order, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>' data-i18n="assets.action.edit">Edit</button>
                <button class="btn btn-sm btn-danger inbound-delete" data-id="<?= (int)$order['id']; ?>" data-assets="<?= (int)$order['asset_count']; ?>" data-order="<?= htmlspecialchars($order['order_number']); ?>" data-bs-toggle="modal" data-bs-target="#deleteModal" data-target="inbound" data-i18n="assets.action.delete">Delete</button>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="<?= $is_manager ? '9' : '8'; ?>" class="text-center" data-i18n="assets.inbound.none">No inbound orders</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>
<div class="card mb-4">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h3 class="mb-0" data-i18n="assets.list.title">Asset Inventory</h3>
    <?php if ($is_manager): ?>
    <div class="d-flex flex-wrap gap-2">
      <a href="assets_export.php" class="btn btn-outline-secondary" id="exportAssets" data-i18n="assets.export">Export to Excel</a>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assetModal" data-mode="create" data-i18n="assets.add">New Asset</button>
    </div>
    <?php endif; ?>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-bordered align-middle mb-0 asset-table-nowrap">
        <thead class="table-light">
          <tr>
            <?php if ($is_manager): ?>
            <th data-i18n="assets.table.order_number">Order #</th>
            <?php endif; ?>
            <th data-i18n="assets.table.asset_code">Asset Code</th>
            <th data-i18n="assets.table.category">Category</th>
            <th data-i18n="assets.table.model">Model</th>
            <th data-i18n="assets.table.organization">Owning Unit</th>
            <th data-i18n="assets.table.remarks">Remarks</th>
            <th data-i18n="assets.table.location">Location</th>
            <th data-i18n="assets.table.owner">Responsible</th>
            <th data-i18n="assets.table.status">Status</th>
            <th data-i18n="assets.table.image">Photo</th>
            <th data-i18n="assets.table.updated_at">Updated</th>
            <th data-i18n="assets.table.actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($assets): ?>
            <?php foreach ($assets as $asset): ?>
            <?php $canEdit = $is_manager || (int)$asset['owner_member_id'] === (int)$member_id; ?>
            <tr data-asset-id="<?= (int)$asset['id']; ?>">
              <?php if ($is_manager): ?>
              <td><?= htmlspecialchars($asset['order_number']); ?></td>
              <?php endif; ?>
              <td><?= htmlspecialchars($asset['asset_code']); ?></td>
              <td><?= htmlspecialchars($asset['category']); ?></td>
              <td><?= htmlspecialchars($asset['model']); ?></td>
              <?php $organizationLabel = trim($asset['organization'] ?? ''); ?>
              <td><?= htmlspecialchars($organizationLabel === '' ? '-' : $organizationLabel); ?></td>
              <?php
                $remarksRaw = trim((string)($asset['remarks'] ?? ''));
                $remarksSingle = $remarksRaw === '' ? '' : preg_replace("/\s+/u", ' ', $remarksRaw);
              ?>
              <td>
                <?php if ($remarksSingle === ''): ?>
                  <span class="text-muted">-</span>
                <?php else: ?>
                  <?= htmlspecialchars($remarksSingle); ?>
                <?php endif; ?>
              </td>
              <?php
                $locationLabel = trim(($asset['office_name'] ? $asset['office_name'] : '') . ($asset['seat_label'] ? (' / ' . $asset['seat_label']) : ''));
              ?>
              <td><?= htmlspecialchars($locationLabel === '' ? '-' : $locationLabel); ?></td>
              <?php
                $customOwner = trim((string)($asset['owner_external_name'] ?? ''));
                $memberOwner = trim((string)($asset['owner_name'] ?? ''));
                $ownerDisplay = $customOwner !== '' ? $customOwner : ($memberOwner !== '' ? $memberOwner : '-');
              ?>
              <td><?= htmlspecialchars($ownerDisplay); ?></td>
              <td><span data-i18n="assets.status.<?= htmlspecialchars($asset['status']); ?>"><?= htmlspecialchars($asset['status']); ?></span></td>
              <td>
                <?php if (!empty($asset['image_path'])): ?>
                  <a href="<?= htmlspecialchars($asset['image_path']); ?>" target="_blank"><img src="<?= htmlspecialchars($asset['image_path']); ?>" alt="asset" class="img-thumbnail" style="max-height:48px;"></a>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <?php
                $timestampLabel = $asset['updated_at'] ?? $asset['created_at'] ?? '';
              ?>
              <td><?= htmlspecialchars($timestampLabel === '' ? '-' : $timestampLabel); ?></td>
              <td>
                <?php
                  $gotoUrl = '';
                  if ($assetLinkPrefix !== '') {
                      $rawCode = (string)($asset['asset_code'] ?? '');
                      if ($rawCode !== '') {
                          $codeSuffix = normalize_asset_suffix($rawCode, $assetCodePrefix);
                          if ($codeSuffix !== '') {
                              $gotoUrl = $assetLinkPrefix . $codeSuffix;
                          }
                      }
                  }
                ?>
                <?php if ($gotoUrl !== ''): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary me-1 qr-btn asset-goto" data-url="<?= htmlspecialchars($gotoUrl); ?>" data-i18n="assets.action.goto">GoTo</button>
                <?php endif; ?>
                <?php if ($canEdit): ?>
                <button class="btn btn-sm btn-outline-primary asset-edit" data-bs-toggle="modal" data-bs-target="#assetModal" data-mode="edit" data-member-role="<?= $is_manager ? 'manager' : 'member'; ?>" data-asset='<?= json_encode($asset, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>' data-i18n="assets.action.edit">Edit</button>
                <?php endif; ?>
                <?php if ($is_manager): ?>
                <button class="btn btn-sm btn-outline-danger asset-delete" data-id="<?= (int)$asset['id']; ?>" data-code="<?= htmlspecialchars($asset['asset_code']); ?>" data-bs-toggle="modal" data-bs-target="#deleteModal" data-target="asset" data-i18n="assets.action.delete">Delete</button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="<?= $is_manager ? '12' : '11'; ?>" class="text-center" data-i18n="assets.none">No assets</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<form id="deleteInboundForm" method="post" class="d-none">
  <input type="hidden" name="action" value="delete_inbound">
  <input type="hidden" name="id" id="deleteInboundId">
</form>
<form id="deleteAssetForm" method="post" class="d-none">
  <input type="hidden" name="action" value="delete_asset">
  <input type="hidden" name="id" id="deleteAssetId">
</form>

<?php if ($is_manager): ?>
<div class="card mb-4">
  <div class="card-header">
    <h3 class="mb-0" data-i18n="assets.assignments.title">Member Asset Responsibilities</h3>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0 align-middle asset-table-nowrap">
        <thead class="table-light">
          <tr>
            <th data-i18n="assets.assignments.member">Member</th>
            <th data-i18n="assets.assignments.asset_code">Asset Code</th>
            <th data-i18n="assets.assignments.organization">Owning Unit</th>
            <th data-i18n="assets.assignments.category">Category</th>
            <th data-i18n="assets.assignments.model">Model / Configuration</th>
            <th data-i18n="assets.assignments.location">Location</th>
            <th data-i18n="assets.assignments.status">Status</th>
            <th data-i18n="assets.assignments.updated_at">Updated</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($memberAssets): ?>
            <?php foreach ($memberAssets as $record): ?>
              <?php if (!empty($record['assets'])): ?>
                <?php foreach ($record['assets'] as $assignedAsset): ?>
                  <?php
                    $assignmentLocation = trim(($assignedAsset['office_name'] ? $assignedAsset['office_name'] : '') . ($assignedAsset['seat_label'] ? (' / ' . $assignedAsset['seat_label']) : ''));
                    $assignmentOrg = trim((string)($assignedAsset['organization'] ?? ''));
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($record['member_name']); ?></td>
                    <td><?= htmlspecialchars($assignedAsset['asset_code']); ?></td>
                    <td><?= htmlspecialchars($assignmentOrg === '' ? '-' : $assignmentOrg); ?></td>
                    <td><?= htmlspecialchars($assignedAsset['category'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($assignedAsset['model'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($assignmentLocation === '' ? '-' : $assignmentLocation); ?></td>
                    <td><span data-i18n="assets.status.<?= htmlspecialchars($assignedAsset['status']); ?>"><?= htmlspecialchars($assignedAsset['status']); ?></span></td>
                    <td><?= htmlspecialchars($assignedAsset['updated_at'] ?: '-'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td><?= htmlspecialchars($record['member_name']); ?></td>
                  <td colspan="7" class="text-muted" data-i18n="assets.assignments.member_empty">No assets assigned.</td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center" data-i18n="assets.assignments.none">No member asset data</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($is_manager): ?>
<div class="modal fade" id="assetSettingsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <form class="modal-content" method="post">
      <input type="hidden" name="action" value="save_settings">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="assets.settings.title">General Settings</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted" data-i18n="assets.settings.description">Configure global options for asset management.</p>
        <div class="row g-3 align-items-end">
          <div class="col-md-6 col-lg-4">
            <label class="form-label" data-i18n="assets.settings.code_prefix">Asset Code Prefix</label>
            <input type="text" class="form-control" name="code_prefix" value="<?= htmlspecialchars($assetCodePrefix); ?>" maxlength="30">
            <div class="form-text" data-i18n="assets.settings.code_prefix_hint">This prefix appears before the asset code input.</div>
          </div>
          <div class="col-md-6 col-lg-4">
            <label class="form-label" data-i18n="assets.settings.link_prefix">Asset Link Prefix</label>
            <input type="text" class="form-control" name="link_prefix" value="<?= htmlspecialchars($assetLinkPrefix); ?>" maxlength="255">
            <div class="form-text" data-i18n="assets.settings.link_prefix_hint">Combine with the asset code suffix to reach the external platform.</div>
          </div>
          <div class="col-md-6 col-lg-4">
            <label class="form-label" data-i18n="assets.settings.sync_api_prefix">Sync API Prefix</label>
            <input type="text" class="form-control" name="sync_api_prefix" value="<?= htmlspecialchars($assetSyncApiPrefix); ?>" maxlength="255">
            <div class="form-text" data-i18n="assets.settings.sync_api_prefix_hint">Append the asset code suffix to query the integration endpoint.</div>
          </div>
        </div>
        <hr class="my-4">
        <h4 class="mb-2" data-i18n="assets.sync.title">Sync Interface</h4>
        <p class="text-muted" data-i18n="assets.sync.description">After saving the prefix, load a sample asset to map JSON keys to local fields.</p>
        <?php if ($assetSyncApiPrefix === ''): ?>
          <div class="alert alert-info" data-i18n="assets.sync.prefix_notice">Provide and save the sync API prefix to start configuring mappings.</div>
        <?php else: ?>
          <div id="syncStatus" class="alert d-none" role="alert"></div>
          <div class="row g-3 align-items-end">
            <div class="col-md-6 col-lg-4">
              <label class="form-label" for="syncSampleInput" data-i18n="assets.sync.sample_input_label">Sample Asset ID</label>
              <input type="text" class="form-control" id="syncSampleInput" data-i18n-placeholder="assets.sync.sample_input_placeholder" placeholder="Enter asset ID or code">
              <div class="form-text" data-i18n="assets.sync.sample_help">The asset code suffix (without the prefix and leading zeros) will be appended to the sync API prefix.</div>
            </div>
            <div class="col-md-6 col-lg-3">
              <button type="button" class="btn btn-outline-secondary w-100" id="syncFetchBtn" data-i18n="assets.sync.load_button">Load Sample</button>
            </div>
          </div>
          <div class="mt-3 d-none" id="syncSampleResult">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-2 gap-2">
              <h5 class="mb-0" data-i18n="assets.sync.sample_result_title">Sample Response</h5>
              <div class="text-muted small">
                <span data-i18n="assets.sync.sample_url">Requested URL:</span>
                <code id="syncSampleUrl"></code>
              </div>
            </div>
            <pre class="bg-body-tertiary border rounded p-3 text-start" id="syncSampleJson" style="max-height:320px; overflow:auto;"></pre>
          </div>
          <div class="mt-3 d-none" id="syncMappingSection">
            <h5 data-i18n="assets.sync.mapping_title">Attribute Mapping</h5>
            <p class="text-muted" data-i18n="assets.sync.mapping_description">Select the JSON key that matches each asset field.</p>
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle mb-3">
                <thead class="table-light">
                  <tr>
                    <th data-i18n="assets.sync.mapping.attribute">Attribute</th>
                    <th data-i18n="assets.sync.mapping.json_key">JSON Key</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($syncAttributeOptions as $attribute => $meta): ?>
                  <?php $currentMappingValue = $assetSyncMapping[$attribute] ?? ''; ?>
                  <tr>
                    <td><span data-i18n="<?= htmlspecialchars($meta['key']); ?>"><?= htmlspecialchars($meta['default']); ?></span></td>
                    <td>
                      <select class="form-select form-select-sm sync-map-select" data-attribute="<?= htmlspecialchars($attribute); ?>" data-current="<?= htmlspecialchars($currentMappingValue); ?>">
                        <option value="" data-i18n="assets.sync.mapping.none">Not linked</option>
                      </select>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <p class="text-muted d-none" id="syncNoKeys" data-i18n="assets.sync.no_keys">No scalar values detected in the sample response.</p>
            <div class="d-flex justify-content-end">
              <button type="button" class="btn btn-primary" id="syncSaveMapping" data-i18n="assets.sync.save_button">Save Mapping</button>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="assets.cancel">Cancel</button>
        <button type="submit" class="btn btn-primary" data-i18n="assets.settings.save">Save Settings</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="modal fade" id="inboundModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post">
      <input type="hidden" name="action" value="save_inbound">
      <input type="hidden" name="id" id="inbound-id">
      <div class="modal-header">
        <h5 class="modal-title" id="inboundModalLabel" data-i18n="assets.inbound.add">New Inbound</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" data-i18n="assets.inbound.order_number">Order #</label>
            <input type="text" class="form-control" name="order_number" id="inbound-order" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" data-i18n="assets.inbound.date">Inbound Date</label>
            <input type="date" class="form-control" name="inbound_date" id="inbound-date">
          </div>
          <div class="col-md-6">
            <label class="form-label" data-i18n="assets.inbound.supplier">Supplier</label>
            <input type="text" class="form-control" name="supplier" id="inbound-supplier">
          </div>
          <div class="col-md-6">
            <label class="form-label" data-i18n="assets.inbound.supplier_lead">Supplier Lead</label>
            <input type="text" class="form-control" name="supplier_lead" id="inbound-supplier-lead">
          </div>
          <div class="col-md-6">
            <label class="form-label" data-i18n="assets.inbound.receiver_lead">Receiver Lead</label>
            <input type="text" class="form-control" name="receiver_lead" id="inbound-receiver-lead">
          </div>
          <div class="col-md-6">
            <label class="form-label" data-i18n="assets.inbound.location">Location</label>
            <input type="text" class="form-control" name="location" id="inbound-location">
          </div>
          <div class="col-12">
            <label class="form-label" data-i18n="assets.inbound.notes">Notes</label>
            <textarea class="form-control" name="notes" id="inbound-notes" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="assets.cancel">Cancel</button>
        <button type="submit" class="btn btn-primary" data-i18n="assets.save">Save</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="assetModal" tabindex="-1" data-asset-prefix="<?= htmlspecialchars($assetCodePrefix); ?>">
  <div class="modal-dialog modal-xl">
    <form class="modal-content" method="post" enctype="multipart/form-data" id="assetForm">
      <input type="hidden" name="action" value="save_asset">
      <input type="hidden" name="id" id="asset-id">
      <?php if (!$is_manager): ?>
      <input type="hidden" name="inbound_order_id" id="asset-inbound">
      <?php endif; ?>
      <div class="modal-header">
        <h5 class="modal-title" id="assetModalLabel" data-i18n="assets.add">New Asset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <?php if ($is_manager): ?>
          <div class="col-md-4">
            <label class="form-label" data-i18n="assets.form.inbound">Inbound Order</label>
            <select class="form-select" name="inbound_order_id" id="asset-inbound" required>
              <option value="" data-i18n="assets.form.inbound_placeholder">Select inbound</option>
              <?php foreach ($inboundOptions as $opt): ?>
              <option value="<?= (int)$opt['id']; ?>"><?= htmlspecialchars($opt['order_number']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="col-md-4">
            <label class="form-label" data-i18n="assets.form.asset_code">Asset Code</label>
            <div class="input-group">
              <span class="input-group-text<?= $assetCodePrefix === '' ? ' d-none' : ''; ?>" id="asset-code-prefix-display"><?= htmlspecialchars($assetCodePrefix); ?></span>
              <input type="text" class="form-control" name="asset_code_suffix" id="asset-code-suffix" data-i18n-placeholder="assets.form.asset_code_suffix_placeholder" placeholder="AUTO">
            </div>
            <input type="hidden" name="asset_code_use_prefix" id="asset-code-use-prefix" value="<?= $assetCodePrefix !== '' ? '1' : '0'; ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label" data-i18n="assets.form.status">Status</label>
            <select class="form-select" name="status" id="asset-status">
              <option value="in_use" data-i18n="assets.status.in_use">In Use</option>
              <option value="maintenance" data-i18n="assets.status.maintenance">Under Maintenance</option>
              <option value="pending" data-i18n="assets.status.pending">Pending Allocation</option>
              <option value="lost" data-i18n="assets.status.lost">Lost</option>
              <option value="retired" data-i18n="assets.status.retired">Retired</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" data-i18n="assets.form.category">Category</label>
            <input type="text" class="form-control" name="category" id="asset-category">
          </div>
          <div class="col-md-6">
            <label class="form-label" data-i18n="assets.form.model">Model / Configuration</label>
            <input type="text" class="form-control" name="model" id="asset-model">
          </div>
          <div class="col-md-6">
            <label class="form-label" data-i18n="assets.form.organization">Owning Unit</label>
            <input type="text" class="form-control" name="organization" id="asset-organization">
          </div>
          <div class="col-12">
            <label class="form-label" data-i18n="assets.form.remarks">Remarks</label>
            <textarea class="form-control" name="remarks" id="asset-remarks" rows="3"></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label" data-i18n="assets.form.office">Current Office</label>
            <select class="form-select" name="office_id" id="asset-office" data-office-select="1">
              <option value="" data-i18n="assets.form.none">None</option>
              <?php foreach ($offices as $office): ?>
              <?php
                $officeName = trim((string)$office['name']);
                $officeRegion = trim((string)($office['region'] ?? ''));
                $officeLocation = trim((string)($office['location_description'] ?? ''));
                $infoParts = [];
                if ($officeRegion !== '') {
                    $infoParts[] = $officeRegion;
                }
                if ($officeLocation !== '') {
                    $infoParts[] = $officeLocation;
                }
                $fallbackLabel = $officeName;
                if ($infoParts) {
                    $fallbackLabel .= ' · ' . implode(' · ', $infoParts);
                }
              ?>
              <option value="<?= (int)$office['id']; ?>" data-office-option="1" data-office-name="<?= htmlspecialchars($officeName); ?>" data-office-region="<?= htmlspecialchars($officeRegion); ?>" data-office-location="<?= htmlspecialchars($officeLocation); ?>"><?= htmlspecialchars($fallbackLabel); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" data-i18n="assets.form.seat">Workstation</label>
            <select class="form-select" name="seat_id" id="asset-seat">
              <option value="" data-i18n="assets.form.none">None</option>
              <?php foreach ($seats as $seat): ?>
              <option value="<?= (int)$seat['id']; ?>" data-office="<?= (int)$seat['office_id']; ?>"><?= htmlspecialchars($seat['label']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" data-i18n="assets.form.owner">Person in Charge</label>
            <select class="form-select" name="owner_id" id="asset-owner">
              <option value="" data-i18n="assets.form.none">None</option>
              <?php foreach ($members as $member): ?>
              <option value="<?= (int)$member['id']; ?>"><?= htmlspecialchars($member['name']); ?></option>
              <?php endforeach; ?>
              <option value="__external__" data-i18n="assets.form.owner_other">Others</option>
            </select>
            <div class="mt-2 d-none" id="asset-owner-custom-wrapper">
              <input type="text" class="form-control" name="owner_external_name" id="asset-owner-custom" maxlength="150" data-i18n-placeholder="assets.form.owner_other_placeholder" placeholder="Enter responsible person">
              <div class="form-text" data-i18n="assets.form.owner_other_hint">Enter the responsible person's name if they are not listed.</div>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label" data-i18n="assets.form.image">Asset Photo</label>
            <input type="file" class="form-control" name="image" id="asset-image" accept="image/*">
            <div class="form-text" data-i18n="assets.form.image_hint">Upload an asset photo for verification.</div>
            <div id="asset-image-preview" class="mt-2"></div>
          </div>
        </div>
        <div class="mt-4" id="assetLogsSection" style="display:none;">
          <h6 data-i18n="assets.logs.title">Operation History</h6>
          <div class="border rounded p-2" style="max-height:200px; overflow-y:auto;">
            <ul class="list-unstyled mb-0" id="assetLogs"></ul>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="assets.cancel">Cancel</button>
        <button type="submit" class="btn btn-primary" data-i18n="assets.save">Save</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel" data-i18n="assets.delete.title">Delete Asset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="deleteModalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="assets.cancel">Cancel</button>
        <button type="button" class="btn btn-danger" id="deleteConfirmBtn" data-i18n="assets.action.confirm_delete">Confirm Delete</button>
      </div>
    </div>
  </div>
</div>

<style>
  .asset-table-nowrap th,
  .asset-table-nowrap td {
    white-space: nowrap;
  }
  .asset-stats .stats-chip {
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    background: rgba(15, 23, 42, 0.05);
    min-width: 140px;
  }
  :root[data-bs-theme='dark'] .asset-stats .stats-chip {
    background: rgba(148, 163, 184, 0.15);
  }
  .asset-stats .stats-label {
    font-size: 0.85rem;
    color: var(--app-muted-text);
  }
  .asset-stats .stats-value {
    font-size: 1.5rem;
    font-weight: 600;
  }
  .highlight-delete {
    animation: highlightFlash 1s ease-in-out 2;
  }
  @keyframes highlightFlash {
    0%, 100% { background-color: transparent; }
    50% { background-color: var(--app-highlight-bg); }
  }
</style>

<script>
const assetSyncInitialMapping = <?= json_encode($assetSyncMapping, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
(function(){
  const assetModal = document.getElementById('assetModal');
  const inboundModal = document.getElementById('inboundModal');
  const deleteModal = document.getElementById('deleteModal');
  const seatOptions = Array.from(document.querySelectorAll('#asset-seat option[value]'));
  const lastCategoryKey = 'asset-last-category';
  const lastModelKey = 'asset-last-model';
  const assetCodePrefixDisplay = document.getElementById('asset-code-prefix-display');
  const assetCodeSuffixInput = document.getElementById('asset-code-suffix');
  const assetCodeUsePrefixInput = document.getElementById('asset-code-use-prefix');
  const assetInboundField = document.getElementById('asset-inbound');
  const assetOwnerField = document.getElementById('asset-owner');
  const assetOwnerCustomWrapper = document.getElementById('asset-owner-custom-wrapper');
  const assetOwnerCustomInput = document.getElementById('asset-owner-custom');
  const syncFetchBtn = document.getElementById('syncFetchBtn');
  const syncSampleInput = document.getElementById('syncSampleInput');
  const syncSampleResult = document.getElementById('syncSampleResult');
  const syncSampleJson = document.getElementById('syncSampleJson');
  const syncSampleUrl = document.getElementById('syncSampleUrl');
  const syncMappingSection = document.getElementById('syncMappingSection');
  const syncNoKeys = document.getElementById('syncNoKeys');
  const syncStatusEl = document.getElementById('syncStatus');
  const syncSaveBtn = document.getElementById('syncSaveMapping');
  let assetSyncMappingState = {};
  try {
    assetSyncMappingState = Object.assign({}, assetSyncInitialMapping || {});
  } catch (err) {
    assetSyncMappingState = {};
  }
  let syncAvailableKeys = [];
  let deleteTarget = null;

  const getLang = () => document.documentElement.lang || 'zh';

  const getTranslationsMap = () => {
    if (typeof translations !== 'undefined') {
      return translations;
    }
    if (typeof window !== 'undefined' && window.translations) {
      return window.translations;
    }
    return {};
  };

  const translate = (lang, key, fallback) => {
    const dict = getTranslationsMap();
    if (dict && dict[lang] && typeof dict[lang][key] === 'string') {
      return dict[lang][key];
    }
    return typeof fallback !== 'undefined' ? fallback : key;
  };

  const applyTranslationsSafe = () => {
    if (typeof applyTranslations === 'function') {
      applyTranslations();
    }
  };

  function updateSyncStatus(level, key) {
    if (!syncStatusEl) return;
    syncStatusEl.classList.add('alert');
    syncStatusEl.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info');
    if (!key) {
      syncStatusEl.classList.add('d-none');
      syncStatusEl.textContent = '';
      syncStatusEl.removeAttribute('data-i18n');
      return;
    }
    const lang = getLang();
    if (level === 'success') {
      syncStatusEl.classList.add('alert-success');
    } else if (level === 'info') {
      syncStatusEl.classList.add('alert-info');
    } else {
      syncStatusEl.classList.add('alert-danger');
    }
    syncStatusEl.setAttribute('data-i18n', key);
    const translated = translate(lang, key, key);
    syncStatusEl.textContent = translated;
    applyTranslationsSafe();
  }

  function populateSyncSelects(keys) {
    if (!syncMappingSection) return;
    const lang = getLang();
    const selects = Array.from(syncMappingSection.querySelectorAll('.sync-map-select'));
    const normalizedKeys = Array.isArray(keys)
      ? keys.map(value => (typeof value === 'string' ? value.trim() : '')).filter(value => value !== '')
      : [];
    const uniqueKeys = Array.from(new Set(normalizedKeys));
    selects.forEach(select => {
      const attribute = select.getAttribute('data-attribute');
      const mappedValue = attribute && assetSyncMappingState[attribute] ? String(assetSyncMappingState[attribute]) : '';
      const datasetValue = select.getAttribute('data-current') || '';
      const merged = Array.from(new Set([...uniqueKeys, mappedValue, datasetValue].filter(value => value !== '')));
      merged.sort((a, b) => a.localeCompare(b));
      const fragment = document.createDocumentFragment();
      const noneOption = document.createElement('option');
      noneOption.value = '';
      noneOption.setAttribute('data-i18n', 'assets.sync.mapping.none');
      noneOption.textContent = translate(lang, 'assets.sync.mapping.none', 'Not linked');
      fragment.appendChild(noneOption);
      merged.forEach(value => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = value;
        fragment.appendChild(option);
      });
      select.innerHTML = '';
      select.appendChild(fragment);
      let selection = '';
      if (mappedValue && merged.includes(mappedValue)) {
        selection = mappedValue;
      } else if (datasetValue && merged.includes(datasetValue)) {
        selection = datasetValue;
      }
      select.value = selection;
      select.setAttribute('data-current', selection);
    });
    applyTranslationsSafe();
  }

  function filterSeats(officeId) {
    const seatSelect = document.getElementById('asset-seat');
    const currentValue = seatSelect.value;
    seatSelect.innerHTML = '';
    const noneOption = document.createElement('option');
    noneOption.value = '';
    noneOption.setAttribute('data-i18n', 'assets.form.none');
    noneOption.textContent = translate(getLang(), 'assets.form.none', 'None');
    seatSelect.appendChild(noneOption);
    seatOptions.forEach(option => {
      const optionOffice = option.getAttribute('data-office');
      if (!officeId || optionOffice === officeId) {
        seatSelect.appendChild(option.cloneNode(true));
      }
    });
    if (currentValue) {
      seatSelect.value = currentValue;
      if (seatSelect.value !== currentValue) {
        seatSelect.value = '';
      }
    }
    applyTranslationsSafe();
  }

  function updateOwnerCustomVisibility() {
    if (!assetOwnerField || !assetOwnerCustomWrapper) {
      return;
    }
    const showCustom = assetOwnerField.value === '__external__';
    assetOwnerCustomWrapper.classList.toggle('d-none', !showCustom);
    if (!showCustom && assetOwnerCustomInput) {
      assetOwnerCustomInput.value = '';
    }
  }

  if (syncMappingSection) {
    const initialValues = Object.values(assetSyncMappingState || {}).filter(value => typeof value === 'string' && value.trim() !== '');
    if (initialValues.length) {
      syncMappingSection.classList.remove('d-none');
    }
    populateSyncSelects(initialValues);
    if (syncNoKeys) {
      syncNoKeys.classList.add('d-none');
    }
  }

  function handleSyncFetch() {
    if (!syncSampleInput || !syncFetchBtn) {
      return;
    }
    const sampleValue = syncSampleInput.value.trim();
    if (sampleValue === '') {
      updateSyncStatus('error', 'assets.sync.errors.asset_required');
      return;
    }
    syncFetchBtn.disabled = true;
    updateSyncStatus('info', 'assets.sync.status.loading');
    const params = new URLSearchParams();
    params.set('action', 'fetch_sync_preview');
    params.set('asset_id', sampleValue);
    fetch('assets.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params.toString()
    })
      .then(response => response.json())
      .then(data => {
        if (!data || typeof data !== 'object') {
          updateSyncStatus('error', 'assets.sync.status.error');
          return;
        }
        if (!data.success) {
          updateSyncStatus('error', data.message || 'assets.sync.status.error');
          return;
        }
        if (data.mapping && typeof data.mapping === 'object') {
          assetSyncMappingState = Object.assign({}, data.mapping);
        }
        syncAvailableKeys = Array.isArray(data.keys)
          ? data.keys.map(value => (typeof value === 'string' ? value.trim() : '')).filter(value => value !== '')
          : [];
        if (syncSampleResult) {
          syncSampleResult.classList.remove('d-none');
        }
        if (syncSampleJson) {
          syncSampleJson.textContent = data.raw || '';
        }
        if (syncSampleUrl) {
          syncSampleUrl.textContent = data.url || '';
        }
        if (syncMappingSection) {
          syncMappingSection.classList.remove('d-none');
        }
        if (syncNoKeys) {
          syncNoKeys.classList.toggle('d-none', syncAvailableKeys.length > 0);
        }
        populateSyncSelects(syncAvailableKeys.length ? syncAvailableKeys : Object.values(assetSyncMappingState || {}));
        updateSyncStatus('success', data.message || 'assets.sync.status.loaded');
      })
      .catch(() => {
        updateSyncStatus('error', 'assets.sync.status.error');
      })
      .finally(() => {
        syncFetchBtn.disabled = false;
        applyTranslationsSafe();
      });
  }

  if (syncFetchBtn && syncSampleInput) {
    syncFetchBtn.addEventListener('click', handleSyncFetch);
    syncSampleInput.addEventListener('keydown', event => {
      if (event.key === 'Enter') {
        event.preventDefault();
        handleSyncFetch();
      }
    });
  }

  if (syncSaveBtn && syncMappingSection) {
    syncSaveBtn.addEventListener('click', () => {
      const selects = Array.from(syncMappingSection.querySelectorAll('.sync-map-select'));
      const payload = {};
      selects.forEach(select => {
        const attribute = select.getAttribute('data-attribute');
        if (!attribute) {
          return;
        }
        const value = (select.value || '').trim();
        if (value !== '') {
          payload[attribute] = value;
        }
      });
      syncSaveBtn.disabled = true;
      updateSyncStatus('info', 'assets.sync.status.saving');
      const params = new URLSearchParams();
      params.set('action', 'save_sync_mapping');
      params.set('mapping', JSON.stringify(payload));
      fetch('assets.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
      })
        .then(response => response.json())
        .then(data => {
          if (!data || typeof data !== 'object') {
            updateSyncStatus('error', 'assets.sync.status.error');
            return;
          }
          if (!data.success) {
            updateSyncStatus('error', data.message || 'assets.sync.status.error');
            return;
          }
          if (data.mapping && typeof data.mapping === 'object') {
            assetSyncMappingState = Object.assign({}, data.mapping);
          } else {
            assetSyncMappingState = Object.assign({}, payload);
          }
          populateSyncSelects(syncAvailableKeys.length ? syncAvailableKeys : Object.values(assetSyncMappingState || {}));
          updateSyncStatus('success', data.message || 'assets.sync.status.saved');
        })
        .catch(() => {
          updateSyncStatus('error', 'assets.sync.status.error');
        })
        .finally(() => {
          syncSaveBtn.disabled = false;
        });
    });
  }

  if (assetOwnerField) {
    assetOwnerField.addEventListener('change', () => {
      updateOwnerCustomVisibility();
    });
  }

  if (assetModal) {
    assetModal.addEventListener('show.bs.modal', event => {
      const button = event.relatedTarget;
      const mode = button?.getAttribute('data-mode') || 'create';
      const role = button?.getAttribute('data-member-role') || 'manager';
      const form = document.getElementById('assetForm');
      form.reset();
      document.getElementById('asset-id').value = '';
      document.getElementById('asset-image-preview').innerHTML = '';
      document.getElementById('assetLogs').innerHTML = '';
      document.getElementById('assetLogsSection').style.display = 'none';
      if (assetOwnerField) {
        assetOwnerField.value = '';
      }
      if (assetOwnerCustomInput) {
        assetOwnerCustomInput.value = '';
      }
      updateOwnerCustomVisibility();
      if (assetInboundField) {
        assetInboundField.disabled = false;
        assetInboundField.value = '';
      }
      document.getElementById('asset-category').readOnly = false;
      document.getElementById('asset-model').readOnly = false;
      document.getElementById('asset-organization').value = '';
      document.getElementById('asset-remarks').value = '';
      const currentPrefix = assetModal.getAttribute('data-asset-prefix') || '';
      if (assetCodePrefixDisplay) {
        assetCodePrefixDisplay.textContent = currentPrefix;
        if (currentPrefix) {
          assetCodePrefixDisplay.classList.remove('d-none');
        } else {
          assetCodePrefixDisplay.classList.add('d-none');
        }
      }
      if (assetCodeSuffixInput) {
        assetCodeSuffixInput.value = '';
        assetCodeSuffixInput.readOnly = false;
        assetCodeSuffixInput.dataset.usesPrefix = currentPrefix ? '1' : '0';
        assetCodeSuffixInput.dataset.original = '';
      }
      if (assetCodeUsePrefixInput) {
        assetCodeUsePrefixInput.value = currentPrefix ? '1' : '0';
      }
      const title = document.getElementById('assetModalLabel');
      if (mode === 'edit') {
        title.setAttribute('data-i18n', 'assets.edit');
        const asset = JSON.parse(button.getAttribute('data-asset'));
        document.getElementById('asset-id').value = asset.id;
        if (assetInboundField) {
          assetInboundField.value = asset.inbound_order_id;
        }
        if (assetCodeSuffixInput) {
          let suffixValue = asset.asset_code || '';
          let usesPrefix = false;
          if (currentPrefix && suffixValue.startsWith(currentPrefix)) {
            suffixValue = suffixValue.substring(currentPrefix.length);
            usesPrefix = true;
          }
          assetCodeSuffixInput.value = suffixValue;
          assetCodeSuffixInput.dataset.usesPrefix = usesPrefix ? '1' : '0';
          assetCodeSuffixInput.dataset.original = suffixValue;
          if (assetCodeUsePrefixInput) {
            assetCodeUsePrefixInput.value = usesPrefix && currentPrefix ? '1' : '0';
          }
        }
        document.getElementById('asset-status').value = asset.status;
        document.getElementById('asset-category').value = asset.category;
        document.getElementById('asset-model').value = asset.model;
        document.getElementById('asset-organization').value = asset.organization || '';
        document.getElementById('asset-remarks').value = asset.remarks || '';
        document.getElementById('asset-office').value = asset.current_office_id || '';
        filterSeats(asset.current_office_id ? String(asset.current_office_id) : '');
        document.getElementById('asset-seat').value = asset.current_seat_id || '';
        if (assetOwnerField) {
          const ownerId = asset.owner_member_id ? String(asset.owner_member_id) : '';
          if (ownerId) {
            assetOwnerField.value = ownerId;
            if (assetOwnerCustomInput) {
              assetOwnerCustomInput.value = '';
            }
          } else if (asset.owner_external_name) {
            assetOwnerField.value = '__external__';
            if (assetOwnerCustomInput) {
              assetOwnerCustomInput.value = asset.owner_external_name;
            }
          } else {
            assetOwnerField.value = '';
            if (assetOwnerCustomInput) {
              assetOwnerCustomInput.value = '';
            }
          }
        }
        if (asset.image_path) {
          document.getElementById('asset-image-preview').innerHTML = `<a href="${asset.image_path}" target="_blank"><img src="${asset.image_path}" class="img-thumbnail" style="max-height:60px;"></a>`;
        }
        if (role === 'member' && assetInboundField && assetInboundField.tagName === 'SELECT') {
          assetInboundField.disabled = true;
          document.getElementById('asset-category').readOnly = true;
          document.getElementById('asset-model').readOnly = true;
          if (assetCodeSuffixInput) {
            assetCodeSuffixInput.readOnly = true;
          }
        }
        document.getElementById('assetLogsSection').style.display = 'block';
        fetch(`assets.php?asset_logs=${asset.id}`)
          .then(res => res.json())
          .then(logs => {
            const list = document.getElementById('assetLogs');
            list.innerHTML = '';
            if (!logs.length) {
              const li = document.createElement('li');
              li.className = 'text-muted';
              li.setAttribute('data-i18n', 'assets.logs.empty');
              li.textContent = translate(getLang(), 'assets.logs.empty', 'No history yet');
              list.appendChild(li);
            } else {
              logs.forEach(log => {
                const li = document.createElement('li');
                li.className = 'mb-2';
                li.innerHTML = `<strong>${log.created_at}</strong> - ${log.operator_name} (${log.operator_role})<br>${log.action}${log.details ? ': ' + log.details : ''}`;
                list.appendChild(li);
              });
            }
            applyTranslationsSafe();
          });
      } else {
        title.setAttribute('data-i18n', 'assets.add');
        const lastCategory = localStorage.getItem(lastCategoryKey);
        const lastModel = localStorage.getItem(lastModelKey);
        filterSeats('');
        document.getElementById('asset-office').value = '';
        if (assetCodeSuffixInput) {
          assetCodeSuffixInput.dataset.usesPrefix = currentPrefix ? '1' : '0';
          assetCodeSuffixInput.dataset.original = '';
        }
        if (lastCategory || lastModel) {
          const lang = document.documentElement.lang || 'zh';
          const msg = translate(lang, 'assets.form.reuse_prompt');
          if (confirm(msg)) {
            if (lastCategory) document.getElementById('asset-category').value = lastCategory;
            if (lastModel) document.getElementById('asset-model').value = lastModel;
          }
        }
      }
      updateOwnerCustomVisibility();
      applyTranslationsSafe();
    });
    document.getElementById('asset-office').addEventListener('change', e => {
      filterSeats(e.target.value);
    });
    document.getElementById('assetForm').addEventListener('submit', () => {
      localStorage.setItem(lastCategoryKey, document.getElementById('asset-category').value.trim());
      localStorage.setItem(lastModelKey, document.getElementById('asset-model').value.trim());
      if (assetCodeSuffixInput) {
        const prefixValue = assetModal.getAttribute('data-asset-prefix') || '';
        let suffixValue = assetCodeSuffixInput.value.trim();
        if (prefixValue && suffixValue.startsWith(prefixValue)) {
          suffixValue = suffixValue.substring(prefixValue.length);
        }
        assetCodeSuffixInput.value = suffixValue;
        if (assetCodeUsePrefixInput) {
          const usePrefix = prefixValue && assetCodeSuffixInput.dataset.usesPrefix === '1';
          assetCodeUsePrefixInput.value = usePrefix ? '1' : '0';
        }
      }
      if (assetOwnerField && assetOwnerCustomInput) {
        if (assetOwnerField.value === '__external__') {
          assetOwnerCustomInput.value = assetOwnerCustomInput.value.trim();
        } else {
          assetOwnerCustomInput.value = '';
        }
      }
    });
  }

  if (inboundModal) {
    inboundModal.addEventListener('show.bs.modal', event => {
      const button = event.relatedTarget;
      const mode = button?.getAttribute('data-mode') || 'create';
      document.getElementById('inbound-id').value = '';
      document.getElementById('inbound-order').value = '';
      document.getElementById('inbound-date').value = '';
      document.getElementById('inbound-supplier').value = '';
      document.getElementById('inbound-supplier-lead').value = '';
      document.getElementById('inbound-receiver-lead').value = '';
      document.getElementById('inbound-location').value = '';
      document.getElementById('inbound-notes').value = '';
      const title = document.getElementById('inboundModalLabel');
      if (mode === 'edit') {
        title.setAttribute('data-i18n', 'assets.inbound.edit');
        const order = JSON.parse(button.getAttribute('data-order'));
        document.getElementById('inbound-id').value = order.id;
        document.getElementById('inbound-order').value = order.order_number;
        document.getElementById('inbound-date').value = order.arrival_date || '';
        document.getElementById('inbound-supplier').value = order.supplier || '';
        document.getElementById('inbound-supplier-lead').value = order.supplier_lead || '';
        document.getElementById('inbound-receiver-lead').value = order.receiver_lead || '';
        document.getElementById('inbound-location').value = order.arrival_location || '';
        document.getElementById('inbound-notes').value = order.notes || '';
      } else {
        title.setAttribute('data-i18n', 'assets.inbound.add');
      }
      applyTranslationsSafe();
    });
  }

  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', event => {
      const button = event.relatedTarget;
      const target = button?.getAttribute('data-target');
      deleteTarget = target;
      const label = document.getElementById('deleteModalLabel');
      const body = document.getElementById('deleteModalBody');
      const lang = document.documentElement.lang || 'zh';
      body.removeAttribute('data-i18n-params');
      if (target === 'asset') {
        const id = button.getAttribute('data-id');
        const code = button.getAttribute('data-code');
        document.getElementById('deleteAssetId').value = id;
        label.setAttribute('data-i18n', 'assets.delete.title');
        body.setAttribute('data-i18n', 'assets.delete.message');
        body.setAttribute('data-i18n-params', JSON.stringify({ code: code || '' }));
      } else {
        const id = button.getAttribute('data-id');
        const count = button.getAttribute('data-assets');
        const order = button.getAttribute('data-order');
        document.getElementById('deleteInboundId').value = id;
        label.setAttribute('data-i18n', 'assets.inbound.delete.title');
        body.setAttribute('data-i18n', 'assets.inbound.delete.message');
        body.setAttribute('data-i18n-params', JSON.stringify({ order: order || '', count: count || '0' }));
        const row = document.querySelector(`tr[data-order-id="${id}"]`);
        row?.classList.add('highlight-delete');
        setTimeout(() => row?.classList.remove('highlight-delete'), 2000);
      }
      applyTranslationsSafe();
    });
    deleteModal.addEventListener('hidden.bs.modal', () => {
      deleteTarget = null;
    });
    document.getElementById('deleteConfirmBtn').addEventListener('click', () => {
      const lang = document.documentElement.lang || 'zh';
      let proceed = false;
        if (deleteTarget === 'asset') {
          const msg = translate(lang, 'assets.delete.confirm');
          proceed = doubleConfirm(msg);
          if (proceed) {
            document.getElementById('deleteAssetForm').submit();
          }
        } else if (deleteTarget === 'inbound') {
          const msg = translate(lang, 'assets.inbound.delete.confirm');
          proceed = confirm(msg) && doubleConfirm(translate(lang, 'assets.inbound.delete.double'));
          if (proceed) {
            document.getElementById('deleteInboundForm').submit();
          }
        }
      if (!proceed) {
        const modal = bootstrap.Modal.getInstance(deleteModal);
        modal.hide();
      }
    });
  }
})();
</script>

<?php include 'footer.php'; ?>
