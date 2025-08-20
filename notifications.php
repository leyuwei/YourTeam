<?php
include 'auth_manager.php';
include 'header.php';
$notifications = $pdo->query('SELECT * FROM notifications WHERE is_revoked=0 ORDER BY id DESC')->fetchAll();
?>
<div class="d-flex justify-content-between mb-3">
  <h2 data-i18n="notifications.title">Notifications</h2>
  <a class="btn btn-success" href="notification_edit.php" data-i18n="notifications.add">Add Notification</a>
</div>
<table class="table table-bordered">
  <tr><th data-i18n="notifications.table_content">Content</th><th data-i18n="notifications.table_begin">Begin</th><th data-i18n="notifications.table_end">End</th><th data-i18n="notifications.table_actions">Actions</th></tr>
  <?php foreach($notifications as $n): ?>
  <tr>
    <td>
      <?= nl2br(htmlspecialchars($n['content'])); ?>
      <?php
        $stmt = $pdo->prepare('SELECT m.name, nt.status FROM notification_targets nt JOIN members m ON nt.member_id=m.id WHERE nt.notification_id=?');
        $stmt->execute([$n['id']]);
        $targets = $stmt->fetchAll();
      ?>
      <div>
        <button class="btn btn-link p-0 toggle-members" data-id="<?= $n['id']; ?>" data-i18n="notifications.toggle_details">Show Target Details</button>
        <ul class="list-group mt-2" id="members-<?= $n['id']; ?>" style="display:none;">
          <?php foreach($targets as $t): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <?= htmlspecialchars($t['name']); ?>
            <span class="badge bg-secondary" data-i18n="notifications.status.<?= $t['status']; ?>"><?= $t['status']; ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </td>
    <td><?= htmlspecialchars($n['valid_begin_date']); ?></td>
    <td><?= htmlspecialchars($n['valid_end_date']); ?></td>
    <td>
      <a class="btn btn-sm btn-primary" href="notification_edit.php?id=<?= $n['id']; ?>" data-i18n="notifications.action_edit">Edit</a>
      <a class="btn btn-sm btn-danger delete-notification" href="notification_revoke.php?id=<?= $n['id']; ?>" data-i18n="notifications.action_revoke">Revoke</a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<script>
document.querySelectorAll('.toggle-members').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const ul=document.getElementById('members-'+btn.dataset.id);
    ul.style.display=ul.style.display==='none'?'block':'none';
  });
});
document.querySelectorAll('.delete-notification').forEach(link=>{
  link.addEventListener('click',e=>{
    const lang=document.documentElement.lang||'en';
    const msg=translations[lang]['notifications.confirm.revoke'];
    if(!doubleConfirm(msg)) e.preventDefault();
  });
});
</script>
<?php include 'footer.php'; ?>
