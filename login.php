<?php
require_once 'config.php';
if(isset($_SESSION['role'])){
    header('Location: index.php');
    exit();
}
$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $type = $_POST['login_type'] ?? 'member';
    if($type === 'manager'){
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM managers WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if($user && password_verify($password, $user['password'])){
            $_SESSION['manager_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = 'manager';
            header('Location: index.php');
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    } else {
        $name = $_POST['name'] ?? '';
        $identity = $_POST['identity_number'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM members WHERE name=? AND identity_number=?');
        $stmt->execute([$name, $identity]);
        $member = $stmt->fetch();
        if($member){
            $_SESSION['member_id'] = $member['id'];
            $_SESSION['username'] = $member['name'];
            $_SESSION['role'] = 'member';
            header('Location: index.php');
            exit();
        } else {
            $error = 'Invalid name or identity number';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title data-i18n="login.title">Login</title>
<link href="./style/bootstrap.min.css" rel="stylesheet">
<style>
  .container { max-width: 80%; }
</style>
</head>
<body>
<div class="container mt-5">
  <div class="text-end mb-3">
    <button id="langToggle" class="btn btn-outline-secondary btn-sm">English</button>
    <button id="themeToggle" class="btn btn-outline-secondary btn-sm" data-i18n="theme.dark">Dark</button>
  </div>
  <h2 class="text-center mb-4" data-i18n="header.title">Team Management Platform</h2>
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="card">
        <div class="card-header" id="loginTitle" data-i18n="login.title.member">Login</div>
        <div class="card-body">
          <?php if($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
          <form method="post" id="loginForm">
            <div class="mb-3">
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="login_type" id="loginManager" value="manager">
                <label class="form-check-label" for="loginManager" data-i18n="login.radio.manager">Manager</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="login_type" id="loginMember" value="member" checked>
                <label class="form-check-label" for="loginMember" data-i18n="login.radio.member">Member</label>
              </div>
            </div>
            <div id="identityWarning" class="alert alert-warning" data-i18n="login.warning.member"></div>
            <div id="managerFields" style="display:none">
              <div class="mb-3">
                <label class="form-label" data-i18n="login.username">Username</label>
                <input type="text" name="username" class="form-control">
              </div>
              <div class="mb-3">
                <label class="form-label" data-i18n="login.password">Password</label>
                <input type="password" name="password" class="form-control">
              </div>
            </div>
            <div id="memberFields">
              <div class="mb-3">
                <label class="form-label" data-i18n="login.name">Name</label>
                <input type="text" name="name" class="form-control">
              </div>
              <div class="mb-3">
                <label class="form-label" data-i18n="login.identity">Identity Number</label>
                <input type="text" name="identity_number" class="form-control">
              </div>
            </div>
            <button type="submit" class="btn btn-primary w-100" data-i18n="login.button">Login</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="./style/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
  const managerFields=document.getElementById('managerFields');
  const memberFields=document.getElementById('memberFields');
  const identityWarning=document.getElementById('identityWarning');
  document.querySelectorAll('input[name="login_type"]').forEach(r=>{
    r.addEventListener('change',function(){
      const titleEl = document.getElementById('loginTitle');
      if(this.value==='manager'){
        managerFields.style.display='block';
        memberFields.style.display='none';
        titleEl.setAttribute('data-i18n','login.title.manager');
        identityWarning.setAttribute('data-i18n','login.warning.manager');
      }else{
        managerFields.style.display='none';
        memberFields.style.display='block';
        titleEl.setAttribute('data-i18n','login.title.member');
        identityWarning.setAttribute('data-i18n','login.warning.member');
      }
      applyTranslations();
    });
  });
});
</script>
<script src="team_name.js"></script>
<script src="app.js"></script>
</body>
</html>
