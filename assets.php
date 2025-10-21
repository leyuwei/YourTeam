<?php
include 'auth.php';

$is_manager = ($_SESSION['role'] ?? '') === 'manager';
$member_id = $_SESSION['member_id'] ?? null;
$username = $_SESSION['username'] ?? '';

function add_asset_log(PDO $pdo, string $targetType, int $targetId, string $operatorName, string $operatorRole, string $action, string $details = ''): void {
    $stmt = $pdo->prepare("INSERT INTO asset_operation_logs (target_type, target_id, operator_name, operator_role, action, details) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$targetType, $targetId, $operatorName, $operatorRole, $action, $details]);
}

function generate_asset_code(PDO $pdo): string {
    do {
        $code = 'ASSET-' . strtoupper(bin2hex(random_bytes(3)));
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

if (isset($_GET['asset_logs'])) {
    $assetId = (int)$_GET['asset_logs'];
    $stmt = $pdo->prepare('SELECT owner_member_id FROM assets WHERE id=?');
    $stmt->execute([$assetId]);
    $assetOwner = $stmt->fetchColumn();
    if (!$assetOwner) {
        http_response_code(404);
        echo json_encode([]);
        exit;
    }
    if (!$is_manager && $assetOwner != $member_id) {
        http_response_code(403);
        echo json_encode([]);
        exit;
    }
    $stmt = $pdo->prepare('SELECT action, details, operator_name, operator_role, created_at FROM asset_operation_logs WHERE target_type="asset" AND target_id=? ORDER BY created_at DESC');
    $stmt->execute([$assetId]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $errors = [];
    try {
        if ($action === 'save_inbound') {
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
            $officeId = isset($_POST['office_id']) && $_POST['office_id'] !== '' ? (int)$_POST['office_id'] : null;
            $seatId = isset($_POST['seat_id']) && $_POST['seat_id'] !== '' ? (int)$_POST['seat_id'] : null;
            $ownerId = isset($_POST['owner_id']) && $_POST['owner_id'] !== '' ? (int)$_POST['owner_id'] : null;
            $status = $_POST['status'] ?? 'pending';
            $allowedStatus = ['in_use', 'maintenance', 'pending', 'lost', 'retired'];
            if (!in_array($status, $allowedStatus, true)) {
                $status = 'pending';
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
                $stmt = $pdo->prepare('SELECT * FROM assets WHERE id=?');
                $stmt->execute([$id]);
                $existing = $stmt->fetch();
                if (!$existing) {
                    throw new RuntimeException('assets.messages.asset_missing');
                }
                if (!$is_manager && (int)$existing['owner_member_id'] !== (int)$member_id) {
                    throw new RuntimeException('assets.messages.permission_denied');
                }
                $assetCode = trim($_POST['asset_code'] ?? $existing['asset_code']);
                if ($assetCode === '') {
                    $assetCode = generate_asset_code($pdo);
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
                    $assetCode = $existing['asset_code'];
                }
                $update = $pdo->prepare('UPDATE assets SET inbound_order_id=?, asset_code=?, category=?, model=?, current_office_id=?, current_seat_id=?, owner_member_id=?, status=?, updated_at=NOW() WHERE id=?');
                $update->execute([$inboundId, $assetCode, $category, $model, $officeId, $seatId, $ownerId, $status, $id]);
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
                if ((int)$existing['current_office_id'] !== (int)$officeId) $changes[] = 'Office updated';
                if ((int)$existing['current_seat_id'] !== (int)$seatId) $changes[] = 'Seat updated';
                if ((int)$existing['owner_member_id'] !== (int)$ownerId) $changes[] = 'Owner updated';
                if ($existing['status'] !== $status) $changes[] = 'Status: ' . $existing['status'] . ' → ' . $status;
                if ($newPath !== $existing['image_path']) $changes[] = 'Image replaced';
                add_asset_log($pdo, 'asset', $id, $username, $_SESSION['role'], 'Asset updated', implode('; ', $changes));
                $_SESSION['asset_flash'] = ['type' => 'success', 'key' => 'assets.messages.asset_updated', 'default' => 'Asset updated successfully'];
            } else {
                if (!$is_manager) {
                    throw new RuntimeException('assets.messages.permission_denied');
                }
                $assetCode = trim($_POST['asset_code'] ?? '');
                if ($assetCode === '') {
                    $assetCode = generate_asset_code($pdo);
                } else {
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM assets WHERE asset_code=?');
                    $stmt->execute([$assetCode]);
                    if ($stmt->fetchColumn()) {
                        throw new RuntimeException('assets.messages.asset_code_exists');
                    }
                }
                $insert = $pdo->prepare('INSERT INTO assets (inbound_order_id, asset_code, category, model, current_office_id, current_seat_id, owner_member_id, status) VALUES (?,?,?,?,?,?,?,?)');
                $insert->execute([$inboundId, $assetCode, $category, $model, $officeId, $seatId, $ownerId, $status]);
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

$inboundTableStmt = $pdo->query('SELECT io.*, COUNT(a.id) AS asset_count FROM asset_inbound_orders io LEFT JOIN assets a ON a.inbound_order_id = io.id GROUP BY io.id ORDER BY io.arrival_date DESC, io.id DESC');
$inboundOrders = $inboundTableStmt->fetchAll();

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

$categoryStmt = $pdo->prepare('SELECT category, COUNT(*) AS total FROM assets' . ($params ? ' WHERE owner_member_id = ?' : '') . ' GROUP BY category ORDER BY total DESC');
$categoryStmt->execute($params);
$categoryStats = $categoryStmt->fetchAll();

$statusStmt = $pdo->prepare('SELECT status, COUNT(*) AS total FROM assets' . ($params ? ' WHERE owner_member_id = ?' : '') . ' GROUP BY status');
$statusStmt->execute($params);
$statusStats = $statusStmt->fetchAll();

$inboundOptions = $pdo->query('SELECT id, order_number FROM asset_inbound_orders ORDER BY arrival_date DESC, id DESC')->fetchAll();
$members = $pdo->query('SELECT id, name FROM members ORDER BY name')->fetchAll();
$offices = $pdo->query('SELECT id, name FROM offices ORDER BY name')->fetchAll();
$seats = $pdo->query('SELECT id, office_id, label FROM office_seats ORDER BY label')->fetchAll();

include 'header.php';
?>
<div class="mb-4">
  <h2 data-i18n="assets.title">Assets</h2>
  <?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type']); ?>" data-i18n="<?= htmlspecialchars($flash['key']); ?>"><?= htmlspecialchars($flash['default']); ?></div>
  <?php endif; ?>
</div>
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
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="mb-0" data-i18n="assets.list.title">Asset Inventory</h3>
    <div>
      <?php if ($is_manager): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assetModal" data-mode="create" data-i18n="assets.add">New Asset</button>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-bordered align-middle mb-0 asset-table-nowrap">
        <thead class="table-light">
          <tr>
            <th data-i18n="assets.table.order_number">Order #</th>
            <th data-i18n="assets.table.asset_code">Asset Code</th>
            <th data-i18n="assets.table.category">Category</th>
            <th data-i18n="assets.table.model">Model</th>
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
              <td><?= htmlspecialchars($asset['order_number']); ?></td>
              <td><?= htmlspecialchars($asset['asset_code']); ?></td>
              <td><?= htmlspecialchars($asset['category']); ?></td>
              <td><?= htmlspecialchars($asset['model']); ?></td>
              <?php
                $locationLabel = trim(($asset['office_name'] ? $asset['office_name'] : '') . ($asset['seat_label'] ? (' / ' . $asset['seat_label']) : ''));
              ?>
              <td><?= htmlspecialchars($locationLabel === '' ? '-' : $locationLabel); ?></td>
              <td><?= htmlspecialchars($asset['owner_name'] ?: '-'); ?></td>
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
            <tr><td colspan="10" class="text-center" data-i18n="assets.none">No assets</td></tr>
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

<div class="modal fade" id="assetModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <form class="modal-content" method="post" enctype="multipart/form-data" id="assetForm">
      <input type="hidden" name="action" value="save_asset">
      <input type="hidden" name="id" id="asset-id">
      <div class="modal-header">
        <h5 class="modal-title" id="assetModalLabel" data-i18n="assets.add">New Asset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label" data-i18n="assets.form.inbound">Inbound Order</label>
            <select class="form-select" name="inbound_order_id" id="asset-inbound" required>
              <option value="" data-i18n="assets.form.inbound_placeholder">Select inbound</option>
              <?php foreach ($inboundOptions as $opt): ?>
              <option value="<?= (int)$opt['id']; ?>"><?= htmlspecialchars($opt['order_number']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" data-i18n="assets.form.asset_code">Asset Code</label>
            <input type="text" class="form-control" name="asset_code" id="asset-code" placeholder="AUTO">
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
            <label class="form-label" data-i18n="assets.form.office">Current Office</label>
            <select class="form-select" name="office_id" id="asset-office">
              <option value="" data-i18n="assets.form.none">None</option>
              <?php foreach ($offices as $office): ?>
              <option value="<?= (int)$office['id']; ?>"><?= htmlspecialchars($office['name']); ?></option>
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
            </select>
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
(function(){
  const assetModal = document.getElementById('assetModal');
  const inboundModal = document.getElementById('inboundModal');
  const deleteModal = document.getElementById('deleteModal');
  const seatOptions = Array.from(document.querySelectorAll('#asset-seat option[value]'));
  const lastCategoryKey = 'asset-last-category';
  const lastModelKey = 'asset-last-model';
  let deleteTarget = null;

  function filterSeats(officeId) {
    const seatSelect = document.getElementById('asset-seat');
    const currentValue = seatSelect.value;
    seatSelect.innerHTML = '';
    const noneOption = document.createElement('option');
    noneOption.value = '';
    noneOption.setAttribute('data-i18n', 'assets.form.none');
    noneOption.textContent = translations[document.documentElement.lang || 'zh']['assets.form.none'];
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
    applyTranslations();
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
      document.getElementById('asset-inbound').disabled = false;
      document.getElementById('asset-category').readOnly = false;
      document.getElementById('asset-model').readOnly = false;
      document.getElementById('asset-code').readOnly = false;
      const title = document.getElementById('assetModalLabel');
      if (mode === 'edit') {
        title.setAttribute('data-i18n', 'assets.edit');
        const asset = JSON.parse(button.getAttribute('data-asset'));
        document.getElementById('asset-id').value = asset.id;
        document.getElementById('asset-inbound').value = asset.inbound_order_id;
        document.getElementById('asset-code').value = asset.asset_code;
        document.getElementById('asset-status').value = asset.status;
        document.getElementById('asset-category').value = asset.category;
        document.getElementById('asset-model').value = asset.model;
        document.getElementById('asset-office').value = asset.current_office_id || '';
        filterSeats(asset.current_office_id ? String(asset.current_office_id) : '');
        document.getElementById('asset-seat').value = asset.current_seat_id || '';
        document.getElementById('asset-owner').value = asset.owner_member_id || '';
        if (asset.image_path) {
          document.getElementById('asset-image-preview').innerHTML = `<a href="${asset.image_path}" target="_blank"><img src="${asset.image_path}" class="img-thumbnail" style="max-height:60px;"></a>`;
        }
        if (role === 'member') {
          document.getElementById('asset-inbound').disabled = true;
          document.getElementById('asset-category').readOnly = true;
          document.getElementById('asset-model').readOnly = true;
          document.getElementById('asset-code').readOnly = true;
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
              li.textContent = translations[document.documentElement.lang || 'zh']['assets.logs.empty'];
              list.appendChild(li);
            } else {
              logs.forEach(log => {
                const li = document.createElement('li');
                li.className = 'mb-2';
                li.innerHTML = `<strong>${log.created_at}</strong> - ${log.operator_name} (${log.operator_role})<br>${log.action}${log.details ? ': ' + log.details : ''}`;
                list.appendChild(li);
              });
            }
            applyTranslations();
          });
      } else {
        title.setAttribute('data-i18n', 'assets.add');
        const lastCategory = localStorage.getItem(lastCategoryKey);
        const lastModel = localStorage.getItem(lastModelKey);
        filterSeats('');
        document.getElementById('asset-office').value = '';
        if (lastCategory || lastModel) {
          const lang = document.documentElement.lang || 'zh';
          const msg = translations[lang]['assets.form.reuse_prompt'];
          if (confirm(msg)) {
            if (lastCategory) document.getElementById('asset-category').value = lastCategory;
            if (lastModel) document.getElementById('asset-model').value = lastModel;
          }
        }
      }
      applyTranslations();
    });
    document.getElementById('asset-office').addEventListener('change', e => {
      filterSeats(e.target.value);
    });
    document.getElementById('assetForm').addEventListener('submit', () => {
      localStorage.setItem(lastCategoryKey, document.getElementById('asset-category').value.trim());
      localStorage.setItem(lastModelKey, document.getElementById('asset-model').value.trim());
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
      applyTranslations();
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
      if (target === 'asset') {
        const id = button.getAttribute('data-id');
        const code = button.getAttribute('data-code');
        document.getElementById('deleteAssetId').value = id;
        label.setAttribute('data-i18n', 'assets.delete.title');
        body.setAttribute('data-i18n', 'assets.delete.message');
        body.textContent = translations[lang]['assets.delete.message'].replace('{code}', code);
      } else {
        const id = button.getAttribute('data-id');
        const count = button.getAttribute('data-assets');
        const order = button.getAttribute('data-order');
        document.getElementById('deleteInboundId').value = id;
        label.setAttribute('data-i18n', 'assets.inbound.delete.title');
        body.setAttribute('data-i18n', 'assets.inbound.delete.message');
        body.textContent = translations[lang]['assets.inbound.delete.message'].replace('{order}', order).replace('{count}', count);
        const row = document.querySelector(`tr[data-order-id="${id}"]`);
        row?.classList.add('highlight-delete');
        setTimeout(() => row?.classList.remove('highlight-delete'), 2000);
      }
      applyTranslations();
    });
    deleteModal.addEventListener('hidden.bs.modal', () => {
      deleteTarget = null;
    });
    document.getElementById('deleteConfirmBtn').addEventListener('click', () => {
      const lang = document.documentElement.lang || 'zh';
      let proceed = false;
      if (deleteTarget === 'asset') {
        const msg = translations[lang]['assets.delete.confirm'];
        proceed = doubleConfirm(msg);
        if (proceed) {
          document.getElementById('deleteAssetForm').submit();
        }
      } else if (deleteTarget === 'inbound') {
        const msg = translations[lang]['assets.inbound.delete.confirm'];
        proceed = confirm(msg) && doubleConfirm(translations[lang]['assets.inbound.delete.double']);
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
