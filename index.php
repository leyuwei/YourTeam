<?php
include 'header.php';
if($_SESSION['role']==='member'){
    $member_id = $_SESSION['member_id'];
    $pdo->prepare('UPDATE notification_targets nt JOIN notifications n ON nt.notification_id=n.id SET nt.status="seen" WHERE nt.member_id=? AND nt.status="sent" AND n.is_revoked=0 AND CURDATE() BETWEEN n.valid_begin_date AND n.valid_end_date')->execute([$member_id]);
    $stmt = $pdo->prepare('SELECT n.id,n.content,n.valid_begin_date,n.valid_end_date,nt.status FROM notifications n JOIN notification_targets nt ON n.id=nt.notification_id WHERE nt.member_id=? AND n.is_revoked=0 AND CURDATE() BETWEEN n.valid_begin_date AND n.valid_end_date ORDER BY CASE nt.status WHEN \'checked\' THEN 1 ELSE 0 END, n.id DESC');
    $stmt->execute([$member_id]);
    $notifications = $stmt->fetchAll();
}
?>

<?php if($_SESSION['role']==='member'): ?>
<h2 class="mt-4 mb-3" data-i18n="index.notifications" style="font-weight:bold; color:red; font-color:red">Notifications</h2>
<div class="list-group mb-4">
  <?php foreach($notifications as $n): ?>
  <div class="list-group-item">
    <div class="d-flex w-100 justify-content-between">
      <p class="mb-1"><?= nl2br(htmlspecialchars($n['content'])); ?></p>
      <small><?= htmlspecialchars($n['valid_begin_date']); ?> ~ <?= htmlspecialchars($n['valid_end_date']); ?></small>
    </div>
    <div class="mt-2">
      <span class="badge bg-secondary me-2" data-i18n="notifications.status.<?= $n['status']; ?>"><?= $n['status']; ?></span>
      <?php if($n['status']!=='checked'): ?>
      <a class="btn btn-sm btn-outline-success check-notification" href="notification_check.php?id=<?= $n['id']; ?>" data-i18n="notifications.action_check">Check</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if(empty($notifications)): ?>
  <div class="list-group-item" data-i18n="notifications.none">No notifications</div>
  <?php endif; ?>
</div>
<br><br>
<script>
document.querySelectorAll('.check-notification').forEach(link=>{
  link.addEventListener('click',e=>{
    const lang=document.documentElement.lang||'zh';
    const msg=translations[lang]['notifications.confirm.check'];
    if(!confirm(msg)) e.preventDefault();
  });
});
</script>
<?php endif; ?>

<div class="hero-banner text-center mb-4">
  <h1 class="display-4 fw-bold mb-3" data-i18n="index.title">Dashboard</h1>
  <p class="lead" data-i18n="index.info">Use the navigation bar to manage team members, projects, tasks, and workload reports.</p>
</div>

<?php include 'footer.php'; ?>
