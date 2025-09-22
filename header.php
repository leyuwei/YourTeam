<?php include_once 'auth.php'; $current_page = basename($_SERVER['PHP_SELF']); ?>
<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title data-i18n="header.title">Team Management Platform</title>
<link href="./style/bootstrap.min.css" rel="stylesheet">
<link rel="icon" href="favicon.svg" type="image/svg+xml">
<link rel="shortcut icon" href="favicon.svg" type="image/svg+xml">
<link rel="icon" href="favicon.png" type="image/png">
<link rel="apple-touch-icon" href="favicon.png">
<style>
  :root {
    color-scheme: light;
    --app-body-bg: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
    --app-surface-bg: rgba(255, 255, 255, 0.85);
    --app-surface-border: rgba(0, 0, 0, 0.05);
    --app-text-color: #212529;
    --app-muted-text: rgba(33, 37, 41, 0.65);
    --app-table-border: rgba(0, 0, 0, 0.08);
    --app-table-striped-bg: rgba(0, 0, 0, 0.02);
    --app-table-head-bg: rgba(0, 0, 0, 0.03);
    --app-navbar-gradient: linear-gradient(90deg, #1f1f1f, #343a40, #212529);
    --app-nav-link-color: #ffffff;
    --app-nav-link-hover: #ffdd57;
    --app-nav-link-active-bg: rgba(255, 221, 87, 0.9);
    --app-nav-link-active-color: #1f1f1f;
    --app-nav-brand-active-bg: rgba(255, 221, 87, 0.9);
    --app-nav-brand-active-color: #1f1f1f;
    --app-card-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    --app-hero-overlay-bg: rgba(0, 0, 0, 0.4);
    --app-form-control-bg: #ffffff;
    --app-form-control-border: rgba(0, 0, 0, 0.15);
    --app-table-row-hover: rgba(0, 0, 0, 0.05);
    --app-highlight-bg: #fff3cd;
    --app-highlight-surface: #fffdf3;
    --app-highlight-text: #856404;
    --app-highlight-border: #ffc107;
    --app-highlight-button-hover: rgba(255, 193, 7, 0.25);
  }
  :root[data-bs-theme='dark'] {
    color-scheme: dark;
    --app-body-bg: radial-gradient(circle at top, #1a1f2b, #0b0d13 55%, #000000);
    --app-surface-bg: rgba(15, 20, 28, 0.88);
    --app-surface-border: rgba(148, 163, 184, 0.2);
    --app-text-color: #e2e8f0;
    --app-muted-text: rgba(226, 232, 240, 0.65);
    --app-table-border: rgba(148, 163, 184, 0.35);
    --app-table-striped-bg: rgba(148, 163, 184, 0.12);
    --app-table-head-bg: rgba(148, 163, 184, 0.18);
    --app-navbar-gradient: linear-gradient(90deg, #050608, #111827, #050608);
    --app-nav-link-color: #e2e8f0;
    --app-nav-link-hover: #ffdd57;
    --app-nav-link-active-bg: rgba(255, 221, 87, 0.18);
    --app-nav-link-active-color: #ffdd57;
    --app-nav-brand-active-bg: rgba(255, 221, 87, 0.18);
    --app-nav-brand-active-color: #ffdd57;
    --app-card-shadow: 0 0 25px rgba(2, 6, 23, 0.6);
    --app-hero-overlay-bg: rgba(0, 0, 0, 0.65);
    --app-form-control-bg: #0f172a;
    --app-form-control-border: rgba(148, 163, 184, 0.25);
    --app-table-row-hover: rgba(148, 163, 184, 0.1);
    --app-highlight-bg: rgba(250, 204, 21, 0.25);
    --app-highlight-surface: rgba(250, 204, 21, 0.12);
    --app-highlight-text: #facc15;
    --app-highlight-border: #facc15;
    --app-highlight-button-hover: rgba(250, 204, 21, 0.3);
  }
  body {
    min-height: 100vh;
    background: var(--app-body-bg);
    background-size: 400% 400%;
    animation: gradientBG 15s ease infinite;
    color: var(--app-text-color);
    transition: background 0.4s ease, color 0.4s ease;
  }
  body.theme-dark {
    background-attachment: fixed;
  }
  .main-container {
    max-width: 80%;
    background-color: var(--app-surface-bg);
    border-radius: 0.5rem;
    padding: 2rem;
    box-shadow: var(--app-card-shadow);
    border: 1px solid var(--app-surface-border);
    transition: background-color 0.4s ease, box-shadow 0.4s ease, border-color 0.4s ease;
  }
  .member-detail {
    color: var(--app-muted-text) !important;
    font-weight: normal !important;
  }
  .navbar {
    position: relative;
    background: var(--app-navbar-gradient);
    background-size: 200% 200%;
    animation: navGradient 15s ease infinite;
    transition: background 0.4s ease;
  }
  .navbar-brand,
  .navbar-text,
  .navbar .btn,
  .dropdown-item {
    white-space: nowrap;
  }
  .navbar-brand {
    font-weight: 600;
    transition: background-color 0.3s ease, color 0.3s ease;
  }
  .navbar-brand.active {
    color: var(--app-nav-brand-active-color) !important;
    background-color: var(--app-nav-brand-active-bg);
    padding: 0.375rem 0.75rem;
    border-radius: 0.5rem;
  }
  .navbar-nav {
    position: relative;
  }
  .navbar-nav .nav-link {
    position: relative;
    z-index: 1;
    color: var(--app-nav-link-color);
    white-space: nowrap;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    transition: color 0.3s ease, background-color 0.3s ease, box-shadow 0.3s ease;
  }
  @media (min-width: 992px) {
    .navbar-nav {
      flex-wrap: nowrap;
      overflow: visible;
      column-gap: 0.5rem;
    }
    .navbar-nav .nav-item {
      flex: 0 0 auto;
    }
  }
  .navbar-nav .nav-link:hover,
  .navbar-nav .nav-link:focus {
    color: var(--app-nav-link-hover);
    text-decoration: none;
  }
  .navbar-nav .nav-link.active,
  .navbar-nav .nav-link.active:hover,
  .navbar-nav .nav-link.active:focus {
    background-color: var(--app-nav-link-active-bg);
    color: var(--app-nav-link-active-color);
    box-shadow: 0 0 0 1px rgba(255, 221, 87, 0.35);
  }
  .dropdown-menu {
    background-color: var(--app-surface-bg);
    border: 1px solid var(--app-surface-border);
    box-shadow: var(--app-card-shadow);
  }
  .dropdown-menu .dropdown-item.active,
  .dropdown-menu .dropdown-item.active:hover,
  .dropdown-menu .dropdown-item.active:focus {
    background-color: var(--app-nav-link-active-bg);
    color: var(--app-nav-link-active-color);
  }
  .navbar .btn {
    transition: color 0.3s ease, border-color 0.3s ease, background-color 0.3s ease;
  }
  :root[data-bs-theme='dark'] .navbar .btn.btn-outline-light {
    color: #e2e8f0;
    border-color: rgba(226, 232, 240, 0.4);
  }
  :root[data-bs-theme='dark'] .navbar .btn.btn-outline-light:hover {
    background-color: rgba(226, 232, 240, 0.1);
    color: #f8fafc;
  }
  .hero-banner {
    background: var(--app-hero-overlay-bg);
    border-radius: 1rem;
    padding: 4rem 2rem;
    color: #fff;
    text-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
    animation: fadeIn 2s ease;
  }
  @keyframes navGradient {
    0% {background-position: 0% 50%;}
    50% {background-position: 100% 50%;}
    100% {background-position: 0% 50%;}
  }
  @keyframes gradientBG {
    0% {background-position: 0% 50%;}
    50% {background-position: 100% 50%;}
    100% {background-position: 0% 50%;}
  }
  @keyframes fadeIn {
    from {opacity: 0; transform: translateY(-20px);}
    to {opacity: 1; transform: translateY(0);}
  }
  tr[style*="background-color"] > * { background-color: inherit !important; }
  .card,
  .modal-content,
  .list-group-item {
    background-color: var(--app-surface-bg);
    color: var(--app-text-color);
    border-color: var(--app-surface-border);
    transition: background-color 0.4s ease, color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease;
  }
  .form-control,
  .form-select,
  .input-group-text,
  .form-control:focus,
  .form-select:focus {
    background-color: var(--app-form-control-bg);
    color: var(--app-text-color);
    border-color: var(--app-form-control-border);
  }
  .form-control:focus,
  .form-select:focus {
    box-shadow: 0 0 0 0.25rem rgba(255, 221, 87, 0.2);
  }
  .table tbody tr:hover {
    background-color: var(--app-table-row-hover);
  }
  .table {
    --bs-table-bg: var(--app-surface-bg);
    --bs-table-color: var(--app-text-color);
    --bs-table-border-color: var(--app-table-border);
    --bs-table-striped-bg: var(--app-table-striped-bg);
    --bs-table-striped-color: var(--app-text-color);
    --bs-table-hover-bg: var(--app-table-row-hover);
    --bs-table-hover-color: var(--app-text-color);
    color: var(--app-text-color);
  }
  .table thead th {
    background-color: var(--app-table-head-bg);
    color: var(--app-text-color);
    border-color: var(--app-table-border);
  }
  .table.table-bordered {
    border-color: var(--app-table-border);
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
  body.mobile-view {
    background-size: cover;
    animation: none;
  }
  body.mobile-view .main-container {
    max-width: 100%;
    padding: 1.5rem 1rem;
    border-radius: 0.25rem;
    box-shadow: none;
    margin: 1rem auto;
  }
  body.mobile-view .navbar-collapse {
    align-items: stretch;
  }
  body.mobile-view .navbar-nav {
    flex-wrap: wrap;
    overflow: visible;
  }
  body.mobile-view .navbar-text {
    display: none;
  }
  body.mobile-view .navbar .btn {
    width: 100%;
    margin: 0.25rem 0;
    margin-right: 0 !important;
  }
  body.mobile-view .navbar-nav .nav-item {
    width: 100%;
  }
  body.mobile-view .navbar-toggler {
    display: block;
  }
  body.mobile-view .navbar-collapse.collapse {
    display: none !important;
  }
  body.mobile-view .navbar-collapse.collapse.show {
    display: block !important;
  }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand <?php echo ($current_page === 'index.php' ? 'active' : ''); ?>" href="index.php" data-i18n="nav.home">Team Management</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link <?php echo (strpos($current_page, 'member') === 0 ? 'active' : ''); ?>" href="members.php" data-i18n="nav.members">Members</a></li>
          <li class="nav-item"><a class="nav-link <?php echo (strpos($current_page, 'todolist') === 0 ? 'active' : ''); ?>" href="todolist.php" data-i18n="nav.todolist">Todolist</a></li>
          <li class="nav-item"><a class="nav-link <?php echo (strpos($current_page, 'project') === 0 ? 'active' : ''); ?>" href="projects.php" data-i18n="nav.projects">Projects</a></li>
          <li class="nav-item"><a class="nav-link <?php echo (strpos($current_page, 'direction') === 0 ? 'active' : ''); ?>" href="directions.php" data-i18n="nav.directions">Research</a></li>
          <li class="nav-item"><a class="nav-link <?php echo (strpos($current_page, 'office') === 0 ? 'active' : ''); ?>" href="offices.php" data-i18n="nav.offices">Offices</a></li>
          <?php if($_SESSION['role']==='manager'): ?>
          <li class="nav-item"><a class="nav-link <?php echo (strpos($current_page, 'notification') === 0 ? 'active' : ''); ?>" href="notifications.php" data-i18n="nav.notifications">Notifications</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link <?php echo (strpos($current_page, 'reimburse') === 0 ? 'active' : ''); ?>" href="reimbursements.php" data-i18n="nav.reimburse">Reimbursement</a></li>
          <li class="nav-item"><a class="nav-link <?php echo (strpos($current_page, 'task') === 0 ? 'active' : ''); ?>" href="tasks.php" data-i18n="nav.tasks">Tasks</a></li>
          <?php if($_SESSION['role']==='manager'): ?>
          <li class="nav-item"><a class="nav-link <?php echo ($current_page === 'workload.php' ? 'active' : ''); ?>" href="workload.php" data-i18n="nav.workload">Workload</a></li>
          <li class="nav-item"><a class="nav-link <?php echo ($current_page === 'account.php' ? 'active' : ''); ?>" href="account.php" data-i18n="nav.account">Account</a></li>
          <?php endif; ?>
        </ul>
      <span class="navbar-text me-3"><span data-i18n="welcome">Welcome</span>, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
      <button id="langToggle" class="btn btn-outline-light me-2">English</button>
      <button id="themeToggle" class="btn btn-outline-light me-2" data-i18n="theme.dark">Dark</button>
      <a class="btn btn-outline-light" id="logoutLink" href="logout.php" data-i18n="logout">Logout</a>
    </div>
  </div>
</nav>
<div class="container main-container">
