<?php include_once 'auth.php'; $current_page = basename($_SERVER['PHP_SELF']); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title data-i18n="header.title">Team Management Platform</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
  .navbar-nav .nav-link {
    position: relative;
    z-index: 1;
    color: #fff;
    transition: color 0.3s ease;
  }
  .navbar-nav .nav-link:hover { color: #ffdd57; }
  .nav-indicator {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.15);
    border-radius: 0.25rem;
    transition: all 0.3s ease;
    pointer-events: none;
    z-index: 0;
  }
  @keyframes navGradient {
    0% {background-position: 0% 50%;}
    50% {background-position: 100% 50%;}
    100% {background-position: 0% 50%;}
  }
  tr[style*="background-color"] > * { background-color: inherit !important; }
  .navbar-nav { position: relative; }
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
          <?php if($_SESSION['role']==='manager'): ?>
          <li class="nav-item"><a class="nav-link <?php echo (strpos($current_page, 'task') === 0 ? 'active' : ''); ?>" href="tasks.php" data-i18n="nav.tasks">Tasks</a></li>
          <li class="nav-item"><a class="nav-link <?php echo ($current_page === 'workload.php' ? 'active' : ''); ?>" href="workload.php" data-i18n="nav.workload">Workload</a></li>
          <li class="nav-item"><a class="nav-link <?php echo ($current_page === 'account.php' ? 'active' : ''); ?>" href="account.php" data-i18n="nav.account">Account</a></li>
          <?php endif; ?>
        </ul>
      <span class="navbar-text me-3"><span data-i18n="welcome">Welcome</span>, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
      <button id="langToggle" class="btn btn-outline-light me-2">中文</button>
      <button id="themeToggle" class="btn btn-outline-light me-2" data-i18n="theme.dark">Dark</button>
      <a class="btn btn-outline-light" id="logoutLink" href="logout.php" data-i18n="logout">Logout</a>
    </div>
  </div>
</nav>
<div class="container main-container">
