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
  body {
    min-height: 100vh;
    background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
    background-size: 400% 400%;
    animation: gradientBG 15s ease infinite;
  }
  .main-container {
    max-width: 80%;
    background-color: rgba(255, 255, 255, 0.85);
    border-radius: 0.5rem;
    padding: 2rem;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
  }
  .member-detail { color: #CCCCCC !important; font-weight: normal !important; }
  .navbar {
    position: relative;
    background: linear-gradient(90deg, #1f1f1f, #343a40, #212529);
    background-size: 200% 200%;
    animation: navGradient 15s ease infinite;
  }
  .navbar-brand,
  .navbar-text,
  .navbar .btn,
  .dropdown-item {
    white-space: nowrap;
  }
  .navbar-brand {
    font-weight: 600;
  }
  .navbar-brand.active {
    color: #1f1f1f !important;
    background-color: rgba(255, 221, 87, 0.9);
    padding: 0.375rem 0.75rem;
    border-radius: 0.5rem;
  }
  .navbar-nav {
    position: relative;
  }
  .navbar-nav .nav-link {
    position: relative;
    z-index: 1;
    color: #fff;
    white-space: nowrap;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
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
    color: #ffdd57;
    text-decoration: none;
  }
  .navbar-nav .nav-link.active,
  .navbar-nav .nav-link.active:hover,
  .navbar-nav .nav-link.active:focus {
    background-color: rgba(255, 221, 87, 0.9);
    color: #1f1f1f;
    box-shadow: 0 0 0 1px rgba(255, 221, 87, 0.6);
  }
  #moreMenu .nav-link.active,
  #moreMenu .nav-link.active:hover,
  #moreMenu .nav-link.active:focus {
    color: #1f1f1f;
  }
  .dropdown-menu .dropdown-item.active,
  .dropdown-menu .dropdown-item.active:hover,
  .dropdown-menu .dropdown-item.active:focus {
    background-color: rgba(255, 221, 87, 0.9);
    color: #1f1f1f;
  }
  @keyframes navGradient {
    0% {background-position: 0% 50%;}
    50% {background-position: 100% 50%;}
    100% {background-position: 0% 50%;}
  }
  tr[style*="background-color"] > * { background-color: inherit !important; }
  .hero-banner {
    background: rgba(0, 0, 0, 0.4);
    border-radius: 1rem;
    padding: 4rem 2rem;
    color: #fff;
    text-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
    animation: fadeIn 2s ease;
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
  body.mobile-view #moreMenu {
    display: none !important;
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
          <li class="nav-item dropdown d-none" id="moreMenu">
            <a class="nav-link dropdown-toggle" href="#" id="moreDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" data-i18n="nav.more">更多</a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="moreDropdown"></ul>
          </li>
        </ul>
      <span class="navbar-text me-3"><span data-i18n="welcome">Welcome</span>, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
      <button id="langToggle" class="btn btn-outline-light me-2">English</button>
      <button id="themeToggle" class="btn btn-outline-light me-2" data-i18n="theme.dark">Dark</button>
      <a class="btn btn-outline-light" id="logoutLink" href="logout.php" data-i18n="logout">Logout</a>
    </div>
  </div>
</nav>
<div class="container main-container">
