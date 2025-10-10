<?php
include 'auth_manager.php';
include 'header.php';
$notifications = $pdo->query('SELECT * FROM notifications WHERE is_revoked=0 ORDER BY id DESC')->fetchAll();

$regulations = $pdo->query('SELECT * FROM regulations ORDER BY sort_order')->fetchAll();
foreach($regulations as &$r){
    $stmt = $pdo->prepare('SELECT id, original_filename FROM regulation_files WHERE regulation_id=?');
    $stmt->execute([$r['id']]);
    $r['files'] = $stmt->fetchAll();
}
unset($r);
?>
<div class="d-flex justify-content-between mb-3">
  <h2 data-i18n="notifications.title">Notifications</h2>
  <a class="btn btn-success" href="notification_edit.php" data-i18n="notifications.add">Add Notification</a>
</div>
<table class="table table-bordered">
  <tr><th data-i18n="notifications.table_content">Content</th><th data-i18n="notifications.table_begin">Begin</th><th data-i18n="notifications.table_end">End</th><th data-i18n="notifications.table_actions">Actions</th></tr>
  <?php foreach($notifications as $n): ?>
  <?php $isExpired = !empty($n['valid_end_date']) && strtotime($n['valid_end_date']) < strtotime('today'); ?>
  <tr<?= $isExpired ? ' class="notification-expired"' : ''; ?>>
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

<hr class="my-5">

<div class="d-flex justify-content-between mb-3">
  <h2 data-i18n="regulations.title">Regulations</h2>
  <a class="btn btn-success" href="regulation_edit.php" data-i18n="regulations.add">Add Regulation</a>
</div>
<table class="table table-bordered table-hover">
  <thead>
    <tr>
      <th></th>
      <th data-i18n="regulations.table_description">Description</th>
      <th data-i18n="regulations.table_category">Category</th>
      <th data-i18n="regulations.table_date">Date</th>
      <th data-i18n="regulations.table_files">Attachments</th>
      <th data-i18n="regulations.table_actions">Actions</th>
    </tr>
  </thead>
  <tbody id="regulationList">
    <?php foreach($regulations as $r): ?>
    <tr data-id="<?= $r['id']; ?>">
      <td class="drag-handle">&#9776;</td>
      <td class="text-truncate" style="max-width:250px;" title="<?= htmlspecialchars($r['description']); ?>"><?= htmlspecialchars($r['description']); ?></td>
      <td class="text-truncate" style="max-width:150px;" title="<?= htmlspecialchars($r['category']); ?>"><?= htmlspecialchars($r['category']); ?></td>
      <td><?= htmlspecialchars($r['updated_at']); ?></td>
      <td>
        <?php if($r['files']): ?>
        <button class="btn btn-sm btn-info view-details" data-desc="<?= htmlspecialchars($r['description']); ?>" data-files='<?= json_encode($r['files'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>' data-i18n="regulations.action_view">View</button>
        <?php else: ?>-
        <?php endif; ?>
      </td>
      <td>
        <a class="btn btn-sm btn-primary" href="regulation_edit.php?id=<?= $r['id']; ?>" data-i18n="regulations.action_edit">Edit</a>
        <a class="btn btn-sm btn-danger delete-regulation" href="regulation_delete.php?id=<?= $r['id']; ?>" data-i18n="regulations.action_delete">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($regulations)): ?>
    <tr><td colspan="6" data-i18n="regulations.none">No regulations</td></tr>
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

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
document.querySelectorAll('.toggle-members').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const ul=document.getElementById('members-'+btn.dataset.id);
    ul.style.display=ul.style.display==='none'?'block':'none';
  });
});
document.querySelectorAll('.delete-notification').forEach(link=>{
  link.addEventListener('click',e=>{
    const lang=document.documentElement.lang||'zh';
    const msg=translations[lang]['notifications.confirm.revoke'];
    if(!doubleConfirm(msg)) e.preventDefault();
  });
});
document.querySelectorAll('.delete-regulation').forEach(link=>{
  link.addEventListener('click',e=>{
    const lang=document.documentElement.lang||'zh';
    const msg=translations[lang]['regulations.confirm.delete'];
    if(!doubleConfirm(msg)) e.preventDefault();
  });
});
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
Sortable.create(document.getElementById('regulationList'), {
  handle: '.drag-handle',
  animation: 150,
  onEnd: function(){
    const order = Array.from(document.querySelectorAll('#regulationList tr')).map((row,index)=>({id:row.dataset.id, position:index}));
    fetch('regulation_order.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order:order})});
  }
});
</script>

<?php include 'footer.php'; ?>
