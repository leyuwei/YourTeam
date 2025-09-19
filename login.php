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
  :root {
    color-scheme: light;
    --login-body-bg: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
    --login-text-color: #212529;
    --login-card-bg: rgba(255, 255, 255, 0.92);
    --login-card-border: rgba(0, 0, 0, 0.05);
    --login-input-bg: #ffffff;
    --login-input-border: rgba(0, 0, 0, 0.15);
    --login-warning-bg: rgba(255, 193, 7, 0.2);
    --login-warning-text: #725200;
  }
  :root[data-bs-theme='dark'] {
    color-scheme: dark;
    --login-body-bg: radial-gradient(circle at top, #1a1f2b, #0b0d13 55%, #000000);
    --login-text-color: #e2e8f0;
    --login-card-bg: rgba(15, 20, 28, 0.92);
    --login-card-border: rgba(148, 163, 184, 0.2);
    --login-input-bg: #0f172a;
    --login-input-border: rgba(148, 163, 184, 0.25);
    --login-warning-bg: rgba(234, 179, 8, 0.18);
    --login-warning-text: #facc15;
  }
  body {
    min-height: 100vh;
    background: var(--login-body-bg);
    background-size: 400% 400%;
    animation: gradientBG 15s ease infinite;
    color: var(--login-text-color);
    transition: background 0.4s ease, color 0.4s ease;
  }
  .container {
    max-width: 80%;
  }
  .card {
    background-color: var(--login-card-bg);
    border: 1px solid var(--login-card-border);
    box-shadow: 0 0 25px rgba(15, 23, 42, 0.1);
    transition: background-color 0.4s ease, color 0.4s ease, border-color 0.4s ease;
  }
  .card-header {
    background: transparent;
    color: var(--login-text-color);
    border-bottom: 1px solid var(--login-card-border);
  }
  .form-label,
  .form-check-label {
    color: var(--login-text-color);
  }
  .form-control {
    background-color: var(--login-input-bg);
    color: var(--login-text-color);
    border-color: var(--login-input-border);
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
  }
  .form-control:focus {
    border-color: rgba(255, 221, 87, 0.4);
    box-shadow: 0 0 0 0.25rem rgba(255, 221, 87, 0.25);
  }
  .alert-warning {
    background-color: var(--login-warning-bg);
    color: var(--login-warning-text);
    border-color: rgba(250, 204, 21, 0.35);
  }
  body.theme-dark .btn-outline-secondary {
    color: #e2e8f0;
    border-color: rgba(226, 232, 240, 0.4);
  }
  body.theme-dark .btn-outline-secondary:hover,
  body.theme-dark .btn-outline-secondary:focus {
    background-color: rgba(226, 232, 240, 0.1);
    color: #f8fafc;
  }
  @keyframes gradientBG {
    0% {background-position: 0% 50%;}
    50% {background-position: 100% 50%;}
    100% {background-position: 0% 50%;}
  }
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
