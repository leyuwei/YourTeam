<?php
require_once 'auth_manager.php';

$password_msg = '';
$add_msg = '';

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
    }
}

include 'header.php';
?>
<h2 data-i18n="account.title">Account Settings</h2>
<div class="row">
  <div class="col-md-6">
    <h3 data-i18n="account.change_password">Change Password</h3>
    <?php if($password_msg): ?><div class="alert alert-info" data-i18n="<?= $password_msg; ?>"></div><?php endif; ?>
    <form method="post">
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
  <div class="col-md-6">
    <h3 data-i18n="account.add_manager">Add Manager</h3>
    <?php if($add_msg): ?><div class="alert alert-info" data-i18n="<?= $add_msg; ?>"></div><?php endif; ?>
    <form method="post">
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
<?php include 'footer.php'; ?>
