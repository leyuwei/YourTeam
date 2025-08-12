<?php include_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>团队管理平台</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .container { max-width: 80%; }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php" data-i18n="nav.home">Team Management</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="members.php" data-i18n="nav.members">Members</a></li>
        <li class="nav-item"><a class="nav-link" href="projects.php" data-i18n="nav.projects">Projects</a></li>
        <li class="nav-item"><a class="nav-link" href="directions.php" data-i18n="nav.directions">Research</a></li>
        <li class="nav-item"><a class="nav-link" href="tasks.php" data-i18n="nav.tasks">Tasks</a></li>
        <li class="nav-item"><a class="nav-link" href="workload.php" data-i18n="nav.workload">Workload</a></li>
        <li class="nav-item"><a class="nav-link" href="account.php" data-i18n="nav.account">Account</a></li>
      </ul>
      <span class="navbar-text me-3"><span data-i18n="welcome">Welcome</span>, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
      <button id="langToggle" class="btn btn-outline-light me-2">中文</button>
      <button id="themeToggle" class="btn btn-outline-light me-2" data-i18n="theme.dark">Dark</button>
      <a class="btn btn-outline-light" id="logoutLink" href="logout.php" data-i18n="logout">Logout</a>
    </div>
  </div>
</nav>
<div class="container">
