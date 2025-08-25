<?php
include 'header.php';
$regulations = $pdo->query('SELECT * FROM regulations ORDER BY sort_order')->fetchAll();
foreach($regulations as &$r){
    $stmt = $pdo->prepare('SELECT id, original_filename FROM regulation_files WHERE regulation_id=?');
    $stmt->execute([$r['id']]);
    $r['files'] = $stmt->fetchAll();
}
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


<h2 class="mt-5 mb-3" data-i18n="index.regulations">Regulations</h2>
<table class="table table-bordered table-hover">
  <thead>
    <tr>
      <th data-i18n="regulations.table_description">Description</th>
      <th data-i18n="regulations.table_category">Category</th>
      <th data-i18n="regulations.table_date">Date</th>
      <th data-i18n="regulations.table_files">Attachments</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($regulations as $r): ?>
    <tr>
      <td class="text-truncate" style="max-width:250px;" title="<?= htmlspecialchars($r['description']); ?>"><?= htmlspecialchars($r['description']); ?></td>
      <td class="text-truncate" style="max-width:150px;" title="<?= htmlspecialchars($r['category']); ?>"><?= htmlspecialchars($r['category']); ?></td>
      <td><?= htmlspecialchars($r['updated_at']); ?></td>
      <td>
        <?php if($r['files']): ?>
        <button class="btn btn-sm btn-info view-details" data-desc="<?= htmlspecialchars($r['description']); ?>" data-files='<?= json_encode($r['files'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>' data-i18n="regulations.action_view">View</button>
        <?php else: ?>-
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($regulations)): ?>
    <tr><td colspan="4" data-i18n="regulations.none">No regulations</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<div class="modal fade" id="regDetailModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" data-i18n="regulations.title">Regulations</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong data-i18n="regulations.table_description">Description</strong>: <span id="regDesc"></span></p>
        <p><strong data-i18n="regulations.table_files">Attachments</strong>:</p>
        <ul id="regFiles" class="list-group"></ul>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.view-details').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.getElementById('regDesc').textContent=btn.dataset.desc;
    const files=JSON.parse(btn.dataset.files);
    const list=document.getElementById('regFiles');
    list.innerHTML='';
    files.forEach(f=>{
      const li=document.createElement('li');
      li.className='list-group-item';
      const a=document.createElement('a');
      a.href='regulation_file.php?id='+f.id;
      a.textContent=f.original_filename;
      li.appendChild(a);
      list.appendChild(li);
    });
    new bootstrap.Modal(document.getElementById('regDetailModal')).show();
  });
});
</script>
<div class="hero-banner text-center mb-4">
  <h1 class="display-4 fw-bold mb-3" data-i18n="index.title">Dashboard</h1>
  <p class="lead" data-i18n="index.info">Use the navigation bar to manage team members, projects, tasks, and workload reports.</p>
</div>

<?php include 'footer.php'; ?>
