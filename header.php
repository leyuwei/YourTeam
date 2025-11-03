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
    --app-body-bg: linear-gradient(135deg, #e3f2ff, #f5fbff);
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
    --reimburse-batch-completed-bg: rgba(108, 117, 125, 0.12);
    --reimburse-batch-locked-bg: rgba(255, 214, 102, 0.2);
    --notification-expired-bg: rgba(220, 53, 69, 0.08);
    --notification-expired-border: rgba(220, 53, 69, 0.4);
    --task-pending-bg: rgba(255, 193, 7, 0.22);
    --task-pending-border: rgba(255, 193, 7, 0.5);
  }
  :root[data-bs-theme='dark'] {
    color-scheme: dark;
    --app-body-bg: #14161d;
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
    --reimburse-batch-completed-bg: rgba(148, 163, 184, 0.22);
    --reimburse-batch-locked-bg: rgba(250, 204, 21, 0.3);
    --notification-expired-bg: rgba(248, 113, 113, 0.2);
    --notification-expired-border: rgba(248, 113, 113, 0.45);
    --task-pending-bg: rgba(250, 204, 21, 0.3);
    --task-pending-border: rgba(250, 204, 21, 0.55);
  }
  body {
    min-height: 100vh;
    background: var(--app-body-bg);
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
  @keyframes fadeIn {
    from {opacity: 0; transform: translateY(-20px);}
    to {opacity: 1; transform: translateY(0);}
  }
  tr[style*="background-color"] > * { background-color: inherit !important; }
  tr[data-custom-bg] {
    transition: background-color 0.3s ease, color 0.3s ease;
    color: var(--custom-row-text-color, inherit);
  }
  tr[data-custom-bg] > * {
    background-color: inherit !important;
  }
  tr[data-custom-bg] .member-detail {
    color: var(--custom-row-muted-color, inherit) !important;
  }
  .notification-expired,
  .task-row-pending {
    position: relative;
  }
  .notification-expired {
    background-color: var(--notification-expired-bg) !important;
    box-shadow: inset 0.35rem 0 0 var(--notification-expired-border);
  }
  .notification-expired > * {
    background-color: inherit !important;
  }
  .task-row-pending {
    background-color: var(--task-pending-bg) !important;
    box-shadow: inset 0.35rem 0 0 var(--task-pending-border);
  }
  .task-row-pending > * {
    background-color: inherit !important;
  }
  .list-group-item.notification-expired {
    background-color: var(--notification-expired-bg) !important;
    border-color: var(--notification-expired-border) !important;
  }
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
  .asset-row {
    position: relative;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
  }
  .asset-row.asset-row-highlight {
    animation: assetRowFlash 1.4s ease-in-out;
  }
  @keyframes assetRowFlash {
    0% {
      background-color: var(--app-highlight-surface);
      box-shadow: 0 0 0 rgba(0, 0, 0, 0);
    }
    50% {
      background-color: var(--app-highlight-bg);
      box-shadow: 0 0 0 3px var(--app-highlight-border);
    }
    100% {
      background-color: inherit;
      box-shadow: 0 0 0 rgba(0, 0, 0, 0);
    }
  }
  body:not(.theme-dark) tr.asset-row[data-category-color] {
    background-color: var(--asset-category-bg);
    color: var(--asset-category-text);
  }
  body:not(.theme-dark) tr.asset-row[data-category-color] td {
    border-color: var(--asset-category-border, var(--app-table-border));
    background-color: transparent !important;
  }
  body:not(.theme-dark) tr.asset-row[data-category-color] .text-muted {
    color: rgba(33, 37, 41, 0.65) !important;
  }
  body.theme-dark tr.asset-row[data-category-color] {
    background-color: transparent !important;
    color: inherit !important;
  }
  body.theme-dark tr.asset-row[data-category-color] td {
    border-color: var(--app-table-border) !important;
    background-color: transparent !important;
  }
  .asset-stats-card {
    border-radius: 1rem;
    transition: background-color 0.3s ease, border-color 0.3s ease;
  }
  .asset-stats-card .card-body {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  .asset-stats-card .stats-chip {
    padding: 0.6rem 0.9rem;
    border-radius: 0.75rem;
    border: 1px solid transparent;
    min-width: 8rem;
    text-align: center;
    background-color: rgba(148, 163, 184, 0.16);
  }
  .asset-stats-card .stats-label {
    font-size: 0.85rem;
    font-weight: 600;
  }
  .asset-stats-card .stats-value {
    font-size: 1.35rem;
    font-weight: 700;
  }
  body:not(.theme-dark) .asset-stats-card-category {
    background-image: linear-gradient(135deg, rgba(236, 244, 255, 0.95), rgba(249, 245, 255, 0.92));
    border-color: rgba(88, 123, 229, 0.25);
  }
  body:not(.theme-dark) .asset-stats-card-category .stats-chip[data-category-chip] {
    background-color: var(--asset-category-bg);
    color: var(--asset-category-text);
    border-color: var(--asset-category-border, rgba(15, 23, 42, 0.12));
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
  }
  body:not(.theme-dark) .asset-stats-card-category .stats-chip[data-category-chip] .stats-value {
    color: var(--asset-category-text);
  }
  body.theme-dark .asset-stats-card-category {
    background-color: var(--app-surface-bg);
  }
  body.theme-dark .asset-stats-card-category .stats-chip[data-category-chip] {
    background-color: rgba(148, 163, 184, 0.18);
    border-color: rgba(148, 163, 184, 0.3);
    color: var(--app-text-color);
  }
  body.theme-dark .asset-stats-card-category .stats-chip[data-category-chip] .stats-value {
    color: var(--app-text-color);
  }
  .member-assets-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  .member-assets-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem 1rem;
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    background-color: rgba(255, 255, 255, 0.7);
    border: 1px solid var(--app-table-border);
    transition: background-color 0.3s ease, border-color 0.3s ease;
  }
  body.theme-dark .member-assets-row {
    background-color: rgba(15, 23, 42, 0.6);
    border-color: rgba(148, 163, 184, 0.25);
  }
  .member-assets-name {
    font-weight: 600;
    min-width: 160px;
  }
  .member-assets-badges {
    flex: 1 1 auto;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  .asset-assignment-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    appearance: none;
    text-decoration: none;
    border: 1px solid var(--asset-category-border, rgba(15, 23, 42, 0.12));
    background-color: var(--asset-category-bg, rgba(148, 163, 184, 0.16));
    color: var(--asset-category-text, var(--app-text-color));
    transition: transform 0.2s ease;
  }
  .asset-assignment-badge:hover {
    transform: translateY(-1px);
  }
  .asset-assignment-badge:focus-visible {
    outline: none;
    box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.35);
  }
  body.theme-dark .asset-assignment-badge {
    background-color: rgba(148, 163, 184, 0.22);
    border-color: rgba(148, 163, 184, 0.35);
    color: var(--app-text-color);
  }
  .member-assets-empty {
    font-size: 0.9rem;
  }
  .asset-owner-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    border-radius: 999px;
    padding: 0.25rem 0.55rem;
    font-size: 0.75rem;
    font-weight: 500;
    margin-top: 0.35rem;
  }
  .asset-owner-tag--external {
    background-color: rgba(168, 85, 247, 0.15);
    color: #6b21a8;
  }

  .asset-code-link {
    padding: 0;
    margin: 0;
    border: none;
    background: transparent;
    color: var(--bs-link-color, #0d6efd);
    font-weight: 600;
    text-decoration: underline;
    cursor: pointer;
  }
  .asset-code-link:hover {
    color: var(--bs-link-hover-color, #0a58ca);
    text-decoration: none;
  }
  .asset-code-link:focus-visible {
    outline: 2px solid var(--app-highlight-border);
    outline-offset: 2px;
  }
  body.theme-dark .asset-code-link {
    color: #93c5fd;
  }
  body.theme-dark .asset-code-link:hover {
    color: #bfdbfe;
  }

  .asset-member-section {
    padding: 1.5rem;
  }
  .asset-member-section + .asset-member-section {
    border-top: 1px solid var(--app-table-border);
  }
  body.theme-dark .asset-member-section + .asset-member-section {
    border-color: rgba(148, 163, 184, 0.25);
  }
  .asset-member-section .table-responsive {
    margin-top: 1rem;
  }
  .asset-owner-tag--status {
    background-color: rgba(234, 179, 8, 0.2);
    color: #92400e;
  }
  body.theme-dark .asset-owner-tag--external {
    background-color: rgba(168, 85, 247, 0.28);
    color: #e9d5ff;
  }
  body.theme-dark .asset-owner-tag--status {
    background-color: rgba(234, 179, 8, 0.28);
    color: #fef3c7;
  }
  @media (max-width: 576px) {
    .member-assets-name {
      min-width: 100%;
    }
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
          <li class="nav-item"><a class="nav-link <?php echo (strpos($current_page, 'reimburse') === 0 ? 'active' : ''); ?>" href="reimbursements.php" data-i18n="nav.reimburse">Reimbursement</a></li>
          <li class="nav-item"><a class="nav-link <?php echo ($current_page === 'assets.php' ? 'active' : ''); ?>" href="assets.php" data-i18n="nav.assets">Assets</a></li>
          <?php if($_SESSION['role']==='manager'): ?>
          <li class="nav-item"><a class="nav-link <?php echo (strpos($current_page, 'notification') === 0 ? 'active' : ''); ?>" href="notifications.php" data-i18n="nav.notifications">Notifications</a></li>
          <?php endif; ?>
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
