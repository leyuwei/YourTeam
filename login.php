<?php
require_once 'config.php';
if(isset($_SESSION['role'])){
    header('Location: index.php');
    exit();
}
$errorKey = '';
$errorTarget = '';
$activePanel = 'member';
$errorFallbacks = [
    'login.error.manager_invalid' => 'Invalid username or password.',
    'login.error.member_name_required' => 'Please enter your name.',
    'login.error.member_not_found' => 'Account not found. Please confirm your login method.',
    'login.error.member_identity_required' => 'Please enter your identity number.',
    'login.error.member_identity_invalid' => 'Identity number verification failed.',
    'login.error.member_password_required' => 'Please enter your password.',
    'login.error.member_password_invalid' => 'Password verification failed.',
    'login.error.member_mode_mismatch' => 'This account uses a different login method. Please adjust your selection.'
];
$memberMode = $_POST['member_login_mode'] ?? 'identity';
$submittedMemberName = $_POST['name'] ?? '';
$submittedIdentity = $_POST['identity_number'] ?? '';
$submittedMemberPassword = $_POST['member_password'] ?? '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $type = $_POST['login_type'] ?? '';
    if($type === 'manager'){
        $activePanel = 'manager';
        $username = trim($_POST['username'] ?? '');
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
            $errorKey = 'login.error.manager_invalid';
            $errorTarget = 'manager';
        }
    } elseif($type === 'member'){
        $activePanel = 'member';
        $name = trim($_POST['name'] ?? '');
        $memberMode = (($_POST['member_login_mode'] ?? '') === 'password') ? 'password' : 'identity';
        if($name === ''){
            $errorKey = 'login.error.member_name_required';
            $errorTarget = 'member';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM members WHERE name = ?');
            $stmt->execute([$name]);
            $member = $stmt->fetch();
            if(!$member){
                $errorKey = 'login.error.member_not_found';
                $errorTarget = 'member';
            } elseif(($member['login_method'] ?? 'identity') !== $memberMode){
                $errorKey = 'login.error.member_mode_mismatch';
                $errorTarget = 'member';
            } elseif($memberMode === 'identity'){
                $identity = trim($_POST['identity_number'] ?? '');
                if($identity === ''){
                    $errorKey = 'login.error.member_identity_required';
                    $errorTarget = 'member';
                } elseif($member['identity_number'] !== null && hash_equals((string)$member['identity_number'], $identity)){
                    $_SESSION['member_id'] = $member['id'];
                    $_SESSION['username'] = $member['name'];
                    $_SESSION['role'] = 'member';
                    header('Location: index.php');
                    exit();
                } else {
                    $errorKey = 'login.error.member_identity_invalid';
                    $errorTarget = 'member';
                }
            } else {
                $password = $_POST['member_password'] ?? '';
                if($password === ''){
                    $errorKey = 'login.error.member_password_required';
                    $errorTarget = 'member';
                } elseif(!empty($member['password_hash']) && password_verify($password, $member['password_hash'])){
                    $_SESSION['member_id'] = $member['id'];
                    $_SESSION['username'] = $member['name'];
                    $_SESSION['role'] = 'member';
                    header('Location: index.php');
                    exit();
                } else {
                    $errorKey = 'login.error.member_password_invalid';
                    $errorTarget = 'member';
                }
            }
        }
    }
}
if($errorTarget === 'manager'){
    $activePanel = 'manager';
}
if($errorTarget !== 'member'){
    $memberMode = 'identity';
    $submittedMemberName = '';
    $submittedIdentity = '';
    $submittedMemberPassword = '';
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
  .login-toggle-wrapper {
    display: flex;
    justify-content: center;
  }
  .login-toggle-group {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.2);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.18);
    backdrop-filter: blur(6px);
  }
  body.theme-dark .login-toggle-group {
    background: rgba(15, 23, 42, 0.45);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.4);
  }
  .login-toggle-group .btn {
    border: none;
    border-radius: 999px !important;
    font-weight: 600;
    color: var(--login-text-color);
    padding: 0.5rem 1.25rem;
    transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
  }
  .login-toggle-group .btn-check:checked + .btn,
  .login-toggle-group .btn:hover,
  .login-toggle-group .btn:focus {
    background-color: rgba(15, 23, 42, 0.15);
    color: var(--login-text-color);
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.35);
  }
  body.theme-dark .login-toggle-group .btn-check:checked + .btn,
  body.theme-dark .login-toggle-group .btn:hover,
  body.theme-dark .login-toggle-group .btn:focus {
    background-color: rgba(148, 163, 184, 0.25);
    color: #f8fafc;
  }
  .card {
    border: none;
  }
  .login-card {
    position: relative;
    background-color: var(--login-card-bg);
    border-radius: 1.25rem;
    border: 1px solid var(--login-card-border);
    box-shadow: 0 25px 45px rgba(15, 23, 42, 0.12);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.4s ease, border-color 0.4s ease;
  }
  .login-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 30px 60px rgba(15, 23, 42, 0.18);
  }
  .login-card::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    transition: opacity 0.4s ease;
    opacity: 0.6;
  }
  .login-card-manager::before {
    background: linear-gradient(135deg, rgba(17, 94, 89, 0.9), rgba(12, 74, 110, 0.85));
  }
  .login-card-member::before {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.9), rgba(14, 116, 144, 0.85));
  }
  .login-card .card-body {
    position: relative;
    z-index: 1;
  }
  .login-card-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 0.9rem;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    color: #fff;
    background: rgba(0, 0, 0, 0.25);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.3);
  }
  .login-card-manager .login-card-badge { background: rgba(15, 118, 110, 0.6); }
  .login-card-member .login-card-badge { background: rgba(59, 130, 246, 0.6); }
  .login-card-description {
    margin-top: 1.25rem;
    font-size: 1.05rem;
    color: rgba(255, 255, 255, 0.92);
  }
  .login-card-manager .login-card-description { color: rgba(224, 255, 255, 0.9); }
  .login-card-member .login-card-description { color: rgba(240, 253, 250, 0.9); }
  .login-card .form-label,
  .login-card .form-check-label {
    color: var(--login-text-color);
    font-weight: 600;
  }
  .form-control {
    background-color: var(--login-input-bg);
    color: var(--login-text-color);
    border-color: var(--login-input-border);
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
  }
  .login-card-manager .form-control,
  .login-card-member .form-control {
    background: rgba(255, 255, 255, 0.95);
    border-color: rgba(255, 255, 255, 0.4);
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
  <h2 class="text-center mb-5" data-i18n="header.title">Team Management Platform</h2>
  <div class="login-toggle-wrapper mb-4">
    <div class="login-toggle-group" role="group" data-i18n-attr="aria-label:login.switch.label">
      <input type="radio" class="btn-check" name="login_panel" id="loginPanelMember" value="member" data-login-panel-toggle="member" <?= $activePanel === 'member' ? 'checked' : ''; ?>>
      <label class="btn" for="loginPanelMember" data-i18n="login.switch.member">Member</label>
      <input type="radio" class="btn-check" name="login_panel" id="loginPanelManager" value="manager" data-login-panel-toggle="manager" <?= $activePanel === 'manager' ? 'checked' : ''; ?>>
      <label class="btn" for="loginPanelManager" data-i18n="login.switch.manager">Administrator</label>
    </div>
  </div>
  <div class="row g-4 justify-content-center align-items-stretch" data-login-panel-container data-active-panel="<?= htmlspecialchars($activePanel, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="col-xl-4 col-lg-5 col-md-6 login-panel <?= $activePanel === 'manager' ? '' : 'd-none'; ?>" data-login-panel="manager">
      <div class="card login-card login-card-manager h-100">
        <div class="card-body d-flex flex-column">
          <div class="login-card-badge" data-i18n="login.section.manager.title">Administrator Access</div>
          <p class="login-card-description" data-i18n="login.section.manager.description">Sign in to manage teams, projects and members.</p>
          <?php if($errorKey && $errorTarget === 'manager'): ?>
          <div class="alert alert-danger" data-i18n="<?= $errorKey; ?>"><?php echo htmlspecialchars($errorFallbacks[$errorKey] ?? ''); ?></div>
          <?php endif; ?>
          <form method="post" class="mt-4 mt-auto">
            <input type="hidden" name="login_type" value="manager">
            <div class="mb-3">
              <label class="form-label" data-i18n="login.username">Username</label>
              <input type="text" name="username" class="form-control" required data-i18n-placeholder="login.placeholder.username" placeholder="Username">
            </div>
            <div class="mb-3">
              <label class="form-label" data-i18n="login.password">Password</label>
              <input type="password" name="password" class="form-control" required data-i18n-placeholder="login.placeholder.password" placeholder="Password">
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-dark" data-i18n="login.button.manager">Manager Login</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-lg-5 col-md-6 login-panel <?= $activePanel === 'member' ? '' : 'd-none'; ?>" data-login-panel="member">
      <div class="card login-card login-card-member h-100">
        <div class="card-body d-flex flex-column">
          <div class="login-card-badge" data-i18n="login.section.member.title">Member Access</div>
          <p class="login-card-description" data-i18n="login.section.member.description">Use the login method that you configured on the dashboard.</p>
          <?php if($errorKey && $errorTarget === 'member'): ?>
          <div class="alert alert-danger" data-i18n="<?= $errorKey; ?>"><?php echo htmlspecialchars($errorFallbacks[$errorKey] ?? ''); ?></div>
          <?php endif; ?>
          <form method="post" id="memberLoginForm" class="mt-3">
            <input type="hidden" name="login_type" value="member">
            <div class="mb-3">
              <label class="form-label" data-i18n="login.name">Name</label>
              <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($submittedMemberName); ?>" required data-i18n-placeholder="login.placeholder.name" placeholder="Name">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold" data-i18n="login.member.mode.title">Login Method</label>
              <div class="btn-group w-100" role="group" aria-label="Member login method">
                <input type="radio" class="btn-check" name="member_login_mode" id="memberLoginModeIdentity" value="identity" <?= $memberMode === 'identity' ? 'checked' : ''; ?>>
                <label class="btn btn-outline-primary" for="memberLoginModeIdentity" data-i18n="login.member.mode.identity">Identity Number</label>
                <input type="radio" class="btn-check" name="member_login_mode" id="memberLoginModePassword" value="password" <?= $memberMode === 'password' ? 'checked' : ''; ?>>
                <label class="btn btn-outline-primary" for="memberLoginModePassword" data-i18n="login.member.mode.password">Password</label>
              </div>
              <div class="form-text" id="memberModeHint" data-i18n="<?= $memberMode === 'password' ? 'login.member.mode.password_hint' : 'login.member.mode.identity_hint'; ?>"></div>
            </div>
            <div id="memberIdentityFields" class="<?= $memberMode === 'identity' ? '' : 'd-none'; ?>">
              <div class="mb-3">
                <label class="form-label" data-i18n="login.identity">Identity Number</label>
                <input type="text" name="identity_number" class="form-control" value="<?= htmlspecialchars($submittedIdentity); ?>" data-i18n-placeholder="login.placeholder.identity" placeholder="Identity Number">
              </div>
            </div>
            <div id="memberPasswordFields" class="<?= $memberMode === 'password' ? '' : 'd-none'; ?>">
              <div class="mb-3">
                <label class="form-label" data-i18n="login.password">Password</label>
                <input type="password" name="member_password" class="form-control" value="<?= htmlspecialchars($submittedMemberPassword); ?>" data-i18n-placeholder="login.placeholder.password" placeholder="Password">
              </div>
            </div>
            <div class="d-grid mt-auto">
              <button type="submit" class="btn btn-primary" data-i18n="login.button.member">Member Login</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="./style/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
  const panelContainer=document.querySelector('[data-login-panel-container]');
  const panelElements=document.querySelectorAll('[data-login-panel]');
  const panelToggles=document.querySelectorAll('[data-login-panel-toggle]');
  const setActivePanel=panel=>{
    const target=panel==='manager'?'manager':'member';
    if(panelContainer){
      panelContainer.setAttribute('data-active-panel', target);
    }
    panelElements.forEach(el=>{
      const name=el.getAttribute('data-login-panel');
      el.classList.toggle('d-none', name !== target);
    });
    panelToggles.forEach(input=>{
      const value=input.getAttribute('data-login-panel-toggle')||input.value;
      input.checked=value===target;
    });
  };
  panelToggles.forEach(input=>{
    const value=input.getAttribute('data-login-panel-toggle')||input.value;
    input.addEventListener('change',()=>setActivePanel(value));
  });
  setActivePanel(panelContainer ? panelContainer.getAttribute('data-active-panel') : 'member');

  const modeRadios=document.querySelectorAll('input[name="member_login_mode"]');
  const identityFields=document.getElementById('memberIdentityFields');
  const passwordFields=document.getElementById('memberPasswordFields');
  const modeHint=document.getElementById('memberModeHint');
  const updateMemberModeUI=()=>{
    const selected=document.querySelector('input[name="member_login_mode"]:checked');
    const mode=selected ? selected.value : 'identity';
    if(identityFields){ identityFields.classList.toggle('d-none', mode !== 'identity'); }
    if(passwordFields){ passwordFields.classList.toggle('d-none', mode !== 'password'); }
    if(modeHint){
      const hintKey=mode === 'password' ? 'login.member.mode.password_hint' : 'login.member.mode.identity_hint';
      modeHint.setAttribute('data-i18n', hintKey);
    }
    if(typeof applyTranslations === 'function'){
      applyTranslations();
    }
  };
  modeRadios.forEach(radio=>radio.addEventListener('change', updateMemberModeUI));
  updateMemberModeUI();
});
</script>
<script src="team_name.js"></script>
<script src="app.js"></script>
</body>
</html>
