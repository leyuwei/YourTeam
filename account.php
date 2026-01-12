<?php
require_once 'auth_manager.php';

$password_msg = '';
$add_msg = '';
$delete_msg = '';
$data_msg = '';
$data_msg_type = 'info';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if($_POST['action'] === 'change_password'){
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if($new !== $confirm){
            $password_msg = 'account.msg.password_mismatch';
        } else {
            $stmt = $pdo->prepare('SELECT password FROM managers WHERE id = ?');
            $stmt->execute([$_SESSION['manager_id']]);
            $manager = $stmt->fetch();
            if($manager && password_verify($current, $manager['password'])){
                $stmt = $pdo->prepare('UPDATE managers SET password = ? WHERE id = ?');
                $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['manager_id']]);
                $password_msg = 'account.msg.password_updated';
            } else {
                $password_msg = 'account.msg.current_incorrect';
            }
        }
    } elseif($_POST['action'] === 'add_manager'){
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        if($username && $password){
            try{
                $stmt = $pdo->prepare('INSERT INTO managers (username, password) VALUES (?, ?)');
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
                $add_msg = 'account.msg.manager_added';
            } catch(PDOException $e){
                $add_msg = 'account.msg.manager_add_error';
            }
        }
    } elseif($_POST['action'] === 'delete_manager'){
        $manager_id = intval($_POST['manager_id'] ?? 0);
        if($manager_id === $_SESSION['manager_id']){
            $delete_msg = 'account.msg.manager_delete_self';
        } else {
            $count_stmt = $pdo->query('SELECT COUNT(*) FROM managers');
            $manager_count = (int)$count_stmt->fetchColumn();
            if($manager_count <= 1){
                $delete_msg = 'account.msg.manager_delete_last';
            } else {
                $stmt = $pdo->prepare('DELETE FROM managers WHERE id = ?');
                if($stmt->execute([$manager_id])){
                    $delete_msg = 'account.msg.manager_deleted';
                } else {
                    $delete_msg = 'account.msg.manager_delete_error';
                }
            }
        }
    } elseif($_POST['action'] === 'import_data'){
        $confirm = $_POST['confirm_overwrite'] ?? '';
        if($confirm !== 'yes'){
            $data_msg = 'account.data.import_confirm_required';
            $data_msg_type = 'warning';
        } elseif(!isset($_FILES['data_zip']) || $_FILES['data_zip']['error'] !== UPLOAD_ERR_OK){
            $data_msg = 'account.data.import_upload_error';
            $data_msg_type = 'danger';
        } else {
            require_once 'data_transfer.php';
            set_time_limit(0);
            $uploadFolders = [
                'asset_uploads',
                'regulation_uploads',
                'reimburse_uploads',
                'office_layouts',
                'collect_uploads',
                'publish_uploads',
            ];
            $tempDir = sys_get_temp_dir() . '/team_import_' . bin2hex(random_bytes(8));
            mkdir($tempDir, 0777, true);
            $zip = new ZipArchive();
            if($zip->open($_FILES['data_zip']['tmp_name']) === true){
                safe_extract_zip($zip, $tempDir);
                $zip->close();
                $sqlPath = $tempDir . '/database.sql';
                if(!file_exists($sqlPath)){
                    $data_msg = 'account.data.import_missing_sql';
                    $data_msg_type = 'danger';
                } else {
                    try{
                        $sql = file_get_contents($sqlPath);
                        import_database_dump($pdo, $sql);
                        foreach($uploadFolders as $folder){
                            $source = $tempDir . '/' . $folder;
                            $destination = __DIR__ . '/' . $folder;
                            if(is_dir($destination)){
                                remove_directory($destination);
                            }
                            if(is_dir($source)){
                                copy_directory($source, $destination);
                            }
                        }
                        $data_msg = 'account.data.import_success';
                        $data_msg_type = 'success';
                    } catch(Throwable $e){
                        $data_msg = 'account.data.import_error';
                        $data_msg_type = 'danger';
                    }
                }
                remove_directory($tempDir);
            } else {
                $data_msg = 'account.data.import_invalid_zip';
                $data_msg_type = 'danger';
                remove_directory($tempDir);
            }
        }
    }
}

$managerStmt = $pdo->query('SELECT id, username FROM managers ORDER BY id');
$managers = $managerStmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>
<style>
.account-hero{background:linear-gradient(135deg,rgba(13,110,253,0.12),rgba(111,66,193,0.08));border:1px solid rgba(13,110,253,0.2);}
.account-hero-icon{width:3rem;height:3rem;}
.manager-list .list-group-item{border-color:var(--app-surface-border,#e9ecef);}
</style>
<h2 class="mb-3 d-flex align-items-center gap-2">
  <span data-i18n="account.title">Account Settings</span>
</h2>
<div class="account-hero rounded-3 p-4 mb-4 shadow-sm">
  <div class="d-flex align-items-center gap-3 flex-wrap">
    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center account-hero-icon" aria-hidden="true">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6">
        <path d="M12 3 6 5v6c0 3.5 2.5 6.7 6 8 3.5-1.3 6-4.5 6-8V5Z" stroke-linejoin="round"></path>
        <path d="M9.5 11.5 11 13l3.5-3.5" stroke-linecap="round" stroke-linejoin="round"></path>
      </svg>
    </div>
    <div>
      <h4 class="mb-1" data-i18n="account.hero.title">管理员中心</h4>
      <p class="mb-0 text-muted" data-i18n="account.hero.subtitle">帮助团队安全、有序地管理后台账号。</p>
    </div>
  </div>
</div>
<div class="row g-4">
  <div class="col-lg-6">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-body">
        <h3 class="card-title h5 mb-1" data-i18n="account.change_password">Change Password</h3>
        <p class="text-muted" data-i18n="account.section.security_hint">Update your password regularly to protect sensitive information.</p>
        <?php if($password_msg): ?><div class="alert alert-info" data-i18n="<?= $password_msg; ?>"></div><?php endif; ?>
        <form method="post" class="mt-3">
          <input type="hidden" name="action" value="change_password">
          <div class="mb-3">
            <label class="form-label" data-i18n="account.current_password">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="account.new_password">New Password</label>
            <input type="password" name="new_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="account.confirm_password">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary" data-i18n="account.change_password_btn">Change Password</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-body">
        <h3 class="card-title h5 mb-1" data-i18n="account.add_manager">Add Manager</h3>
        <p class="text-muted" data-i18n="account.section.add_hint">Create an additional administrator account for collaborators.</p>
        <?php if($add_msg): ?><div class="alert alert-info" data-i18n="<?= $add_msg; ?>"></div><?php endif; ?>
        <form method="post" class="mt-3">
          <input type="hidden" name="action" value="add_manager">
          <div class="mb-3">
            <label class="form-label" data-i18n="account.username">Username</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="account.password">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-success" data-i18n="account.add_manager_btn">Add Manager</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0 mt-4">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <h3 class="card-title h5 mb-1" data-i18n="account.section.list_title">Administrator Directory</h3>
        <p class="text-muted mb-2" data-i18n="account.section.list_hint">Review who can access the management console. Remove accounts that are no longer needed.</p>
      </div>
    </div>
    <?php if($delete_msg): ?><div class="alert alert-warning" data-i18n="<?= $delete_msg; ?>"></div><?php endif; ?>
    <ul class="list-group manager-list mb-0">
      <?php if(empty($managers)): ?>
        <li class="list-group-item text-muted" data-i18n="account.manager_list_empty">暂无管理员</li>
      <?php else: foreach($managers as $manager): ?>
      <li class="list-group-item d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
          <div class="fw-semibold d-flex align-items-center gap-2">
            <?= htmlspecialchars($manager['username']); ?>
            <?php if($manager['id'] === $_SESSION['manager_id']): ?>
              <span class="badge bg-primary-subtle text-primary border" data-i18n="account.badge.you">当前账号</span>
            <?php endif; ?>
          </div>
          <div class="text-muted small"><span data-i18n="account.manager_id_label">编号</span>: <?= $manager['id']; ?></div>
        </div>
        <?php if($manager['id'] !== $_SESSION['manager_id']): ?>
          <form method="post" class="d-flex align-items-center gap-2" onsubmit="return confirm((translations?.[localStorage.getItem('lang')||'zh']?.['account.delete_confirm']) || '确定要删除该管理员吗？此操作无法撤销。');">
            <input type="hidden" name="action" value="delete_manager">
            <input type="hidden" name="manager_id" value="<?= $manager['id']; ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm" data-i18n="account.delete">删除</button>
          </form>
        <?php else: ?>
          <button type="button" class="btn btn-outline-secondary btn-sm" disabled data-i18n="account.delete_disabled">不能删除当前登录账号</button>
        <?php endif; ?>
      </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>
</div>

<div class="card shadow-sm border-0 mt-4">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <h3 class="card-title h5 mb-1" data-i18n="account.data.title">数据迁移</h3>
        <p class="text-muted mb-2" data-i18n="account.data.subtitle">快速导出与导入站点数据，便于迁移或备份。</p>
      </div>
      <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#dataTransferModal" data-i18n="account.data.open_modal">导入/导出</button>
    </div>
    <?php if($data_msg): ?>
      <div class="alert alert-<?= htmlspecialchars($data_msg_type); ?> mb-0" data-i18n="<?= $data_msg; ?>"></div>
    <?php endif; ?>
  </div>
</div>

<div class="modal fade" id="dataTransferModal" tabindex="-1" aria-labelledby="dataTransferModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="dataTransferModalLabel" data-i18n="account.data.modal_title">数据迁移</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-4">
          <div class="col-md-6">
            <div class="p-3 border rounded-3 h-100">
              <h6 class="mb-2" data-i18n="account.data.export_title">一键导出</h6>
              <p class="text-muted small mb-3" data-i18n="account.data.export_hint">将数据库与所有上传文件打包为 ZIP 下载。</p>
              <a href="data_export.php" class="btn btn-primary" data-i18n="account.data.export_button">导出数据</a>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-3 border rounded-3 h-100">
              <h6 class="mb-2" data-i18n="account.data.import_title">一键导入</h6>
              <p class="text-muted small mb-2" data-i18n="account.data.import_hint">请上传导出的 ZIP 包以恢复数据库及上传内容。</p>
              <div class="alert alert-warning py-2 small" role="alert">
                <div data-i18n="account.data.import_warning_primary">导入会覆盖当前数据库与上传文件，建议先导出备份。</div>
                <div data-i18n="account.data.import_warning_secondary">导入过程中请勿关闭页面，以免造成数据不完整。</div>
              </div>
              <form method="post" enctype="multipart/form-data" onsubmit="return confirm((translations?.[localStorage.getItem('lang')||'zh']?.['account.data.import_confirm']) || '确定要导入并覆盖现有数据吗？');">
                <input type="hidden" name="action" value="import_data">
                <div class="mb-3">
                  <label class="form-label" data-i18n="account.data.import_file_label">选择 ZIP 文件</label>
                  <input type="file" name="data_zip" class="form-control" accept=".zip" required>
                </div>
                <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" name="confirm_overwrite" value="yes" id="confirmOverwrite" required>
                  <label class="form-check-label" for="confirmOverwrite" data-i18n="account.data.import_checkbox">我已了解该操作将覆盖当前数据</label>
                </div>
                <button type="submit" class="btn btn-danger" data-i18n="account.data.import_button">导入并覆盖</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include 'footer.php'; ?>
