<?php
include 'auth_manager.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action = $_POST['action'] ?? '';
    $content = trim($_POST['content'] ?? '');
    $begin = $_POST['valid_begin_date'] ?? '';
    $end = $_POST['valid_end_date'] ?? '';
    $members_selected = $_POST['members'] ?? [];

    if($action==='create_notification' && $content && $begin && $end){
        $stmt = $pdo->prepare('INSERT INTO notifications(content,valid_begin_date,valid_end_date) VALUES(?,?,?)');
        $stmt->execute([$content,$begin,$end]);
        $nid = $pdo->lastInsertId();
        foreach($members_selected as $m){
            $pdo->prepare('INSERT INTO notification_targets(notification_id,member_id) VALUES(?,?)')->execute([$nid,$m]);
        }
    }

    if($action==='update_notification'){
        $nid = $_POST['notification_id'] ?? null;
        if($nid && $content && $begin && $end){
            $stmt = $pdo->prepare('UPDATE notifications SET content=?, valid_begin_date=?, valid_end_date=? WHERE id=?');
            $stmt->execute([$content,$begin,$end,$nid]);
            $pdo->prepare('DELETE FROM notification_targets WHERE notification_id=?')->execute([$nid]);
            foreach($members_selected as $m){
                $pdo->prepare('INSERT INTO notification_targets(notification_id,member_id) VALUES(?,?)')->execute([$nid,$m]);
            }
        }
    }

    header('Location: notifications.php');
    exit();
}

include 'header.php';
$notifications = $pdo->query('SELECT * FROM notifications WHERE is_revoked=0 ORDER BY id DESC')->fetchAll();
$activeNotifications = [];
$expiredNotifications = [];
foreach($notifications as $n){
    $isExpired = !empty($n['valid_end_date']) && strtotime($n['valid_end_date']) < strtotime('today');
    if($isExpired){
        $expiredNotifications[] = $n;
    } else {
        $activeNotifications[] = $n;
    }
}

$regulations = $pdo->query('SELECT * FROM regulations ORDER BY sort_order')->fetchAll();
foreach($regulations as &$r){
    $stmt = $pdo->prepare('SELECT id, original_filename FROM regulation_files WHERE regulation_id=?');
    $stmt->execute([$r['id']]);
    $r['files'] = $stmt->fetchAll();
}
unset($r);

$memberList = $pdo->query("SELECT id,name,department,degree_pursuing,year_of_join FROM members WHERE status='in_work' ORDER BY name")
    ->fetchAll();
?>
<div id="notification-page" class="d-flex justify-content-between mb-3" data-edit-id="<?= htmlspecialchars($_GET['edit'] ?? '') ?>">
  <h2 data-i18n="notifications.title">Notifications</h2>
  <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#notificationModal" data-i18n="notifications.add">Add Notification</button>
</div>
<table class="table table-bordered">
  <tr><th data-i18n="notifications.table_content">Content</th><th data-i18n="notifications.table_begin">Begin</th><th data-i18n="notifications.table_end">End</th><th data-i18n="notifications.table_actions">Actions</th></tr>
  <?php if(empty($activeNotifications)): ?>
  <tr><td colspan="4" data-i18n="notifications.none">No notifications</td></tr>
  <?php endif; ?>
  <?php foreach($activeNotifications as $n): ?>
  <tr>
    <td>
      <?= nl2br(htmlspecialchars($n['content'])); ?>
      <?php
        $stmt = $pdo->prepare('SELECT m.id, m.name, m.department, m.degree_pursuing, m.year_of_join, nt.status FROM notification_targets nt JOIN members m ON nt.member_id=m.id WHERE nt.notification_id=?');
        $stmt->execute([$n['id']]);
        $targets = $stmt->fetchAll();
        $targetIds = array_column($targets,'id');
      ?>
      <div>
        <button class="btn btn-link p-0 toggle-members" data-id="<?= $n['id']; ?>" data-i18n="notifications.toggle_details">Show Target Details</button>
        <div class="target-chip-grid mt-2" id="members-<?= $n['id']; ?>" style="display:none;">
          <?php foreach($targets as $t): ?>
          <div class="target-chip">
            <div class="target-chip-header">
              <div class="fw-semibold"><?= htmlspecialchars($t['name']); ?></div>
              <span class="badge bg-secondary" data-i18n="notifications.status.<?= $t['status']; ?>"><?= $t['status']; ?></span>
            </div>
            <div class="target-chip-meta"><?= htmlspecialchars($t['department'] ?: '-'); ?></div>
            <div class="target-chip-meta">
              <?= htmlspecialchars($t['degree_pursuing'] ?: '-'); ?>
              <?php if(!empty($t['year_of_join'])): ?>· <?= htmlspecialchars($t['year_of_join']); ?><?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </td>
    <td><?= htmlspecialchars($n['valid_begin_date']); ?></td>
    <td><?= htmlspecialchars($n['valid_end_date']); ?></td>
    <td>
      <button class="btn btn-sm btn-primary edit-notification" type="button"
              data-id="<?= $n['id']; ?>"
              data-content="<?= htmlspecialchars($n['content']); ?>"
              data-begin="<?= htmlspecialchars($n['valid_begin_date']); ?>"
              data-end="<?= htmlspecialchars($n['valid_end_date']); ?>"
              data-members='<?= json_encode($targetIds, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'
              data-i18n="notifications.action_edit">Edit</button>
      <a class="btn btn-sm btn-danger delete-notification" href="notification_revoke.php?id=<?= $n['id']; ?>" data-i18n="notifications.action_revoke">Revoke</a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>

<div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="create_notification" id="notificationFormAction">
        <input type="hidden" name="notification_id" id="notificationId" value="">
        <div class="modal-header">
          <h5 class="modal-title" id="notificationModalTitle" data-i18n="notification_edit.title_add">Add Notification</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" data-i18n="notification_edit.label_content">Content</label>
            <textarea name="content" class="form-control" rows="4" required></textarea>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" data-i18n="notification_edit.label_begin">Begin Date</label>
              <input type="date" name="valid_begin_date" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="notification_edit.label_end">End Date</label>
              <input type="date" name="valid_end_date" class="form-control" required>
            </div>
          </div>
          <div class="mt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label mb-0" data-i18n="notification_edit.label_members">Target Members</label>
              <button type="button" id="select-all" class="btn btn-sm btn-outline-secondary" data-i18n="notification_edit.select_all">Select All</button>
            </div>
            <div class="target-select-grid">
              <?php foreach($memberList as $m): ?>
                <label class="target-select-card">
                  <div class="d-flex align-items-start">
                    <input class="form-check-input mt-1 member-checkbox" type="checkbox" name="members[]" value="<?= $m['id']; ?>">
                    <div class="ms-2">
                      <div class="fw-semibold"><?= htmlspecialchars($m['name']); ?></div>
                      <div class="target-select-meta"><?= htmlspecialchars($m['department'] ?: '-'); ?></div>
                      <div class="target-select-meta">
                        <?= htmlspecialchars($m['degree_pursuing'] ?: '-'); ?>
                        <?php if(!empty($m['year_of_join'])): ?>· <?= htmlspecialchars($m['year_of_join']); ?><?php endif; ?>
                      </div>
                    </div>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="notification_edit.cancel">Cancel</button>
          <button type="submit" class="btn btn-primary" id="notificationSubmit" data-i18n="notification_edit.save">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if(!empty($expiredNotifications)): ?>
<div class="mt-4">
  <button class="btn btn-outline-secondary" type="button" id="toggleExpiredNotifications" data-bs-toggle="collapse" data-bs-target="#expiredNotifications" aria-expanded="false" aria-controls="expiredNotifications" data-i18n="notifications.show_expired">Show expired notifications</button>
  <div class="collapse mt-3" id="expiredNotifications">
    <h3 data-i18n="notifications.expired_title">Expired Notifications</h3>
    <table class="table table-bordered">
      <tr><th data-i18n="notifications.table_content">Content</th><th data-i18n="notifications.table_begin">Begin</th><th data-i18n="notifications.table_end">End</th><th data-i18n="notifications.table_actions">Actions</th></tr>
      <?php foreach($expiredNotifications as $n): ?>
      <tr class="notification-expired">
        <td>
          <?= nl2br(htmlspecialchars($n['content'])); ?>
          <?php
            $stmt = $pdo->prepare('SELECT m.id, m.name, m.department, m.degree_pursuing, m.year_of_join, nt.status FROM notification_targets nt JOIN members m ON nt.member_id=m.id WHERE nt.notification_id=?');
            $stmt->execute([$n['id']]);
            $targets = $stmt->fetchAll();
            $targetIds = array_column($targets,'id');
          ?>
          <div>
            <button class="btn btn-link p-0 toggle-members" data-id="<?= $n['id']; ?>" data-i18n="notifications.toggle_details">Show Target Details</button>
            <div class="target-chip-grid mt-2" id="members-<?= $n['id']; ?>" style="display:none;">
              <?php foreach($targets as $t): ?>
              <div class="target-chip">
                <div class="target-chip-header">
                  <div class="fw-semibold"><?= htmlspecialchars($t['name']); ?></div>
                  <span class="badge bg-secondary" data-i18n="notifications.status.<?= $t['status']; ?>"><?= $t['status']; ?></span>
                </div>
                <div class="target-chip-meta"><?= htmlspecialchars($t['department'] ?: '-'); ?></div>
                <div class="target-chip-meta">
                  <?= htmlspecialchars($t['degree_pursuing'] ?: '-'); ?>
                  <?php if(!empty($t['year_of_join'])): ?>· <?= htmlspecialchars($t['year_of_join']); ?><?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </td>
        <td><?= htmlspecialchars($n['valid_begin_date']); ?></td>
        <td><?= htmlspecialchars($n['valid_end_date']); ?></td>
        <td>
          <button class="btn btn-sm btn-primary edit-notification" type="button"
                  data-id="<?= $n['id']; ?>"
                  data-content="<?= htmlspecialchars($n['content']); ?>"
                  data-begin="<?= htmlspecialchars($n['valid_begin_date']); ?>"
                  data-end="<?= htmlspecialchars($n['valid_end_date']); ?>"
                  data-members='<?= json_encode($targetIds, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'
                  data-i18n="notifications.action_edit">Edit</button>
          <a class="btn btn-sm btn-danger delete-notification" href="notification_revoke.php?id=<?= $n['id']; ?>" data-i18n="notifications.action_revoke">Revoke</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<?php endif; ?>

<hr class="my-5">

<style>
  .drag-handle { cursor: grab; }
  .drag-handle:active { cursor: grabbing; }
  .target-chip-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:0.6rem; background:var(--app-table-striped-bg); padding:0.75rem; border-radius:0.75rem; border:1px solid var(--app-table-border); }
  .target-chip { border:1px solid var(--app-table-border); border-radius:0.6rem; padding:0.55rem 0.65rem; background:var(--app-surface-bg); box-shadow:0 1px 4px rgba(0,0,0,0.04); display:flex; flex-direction:column; gap:0.2rem; min-height:88px; }
  .target-chip-header { display:flex; justify-content:space-between; align-items:flex-start; gap:0.35rem; }
  .target-chip .badge { font-size:0.75rem; }
  .target-chip-meta { font-size:0.85rem; color:var(--bs-gray-600); line-height:1.2; }
  .target-select-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:0.5rem; max-height:320px; overflow:auto; padding:0.5rem; background:var(--app-table-striped-bg); border-radius:0.75rem; border:1px solid var(--app-table-border); }
  .target-select-card { border:1px solid var(--app-table-border); border-radius:0.55rem; padding:0.5rem 0.65rem; background:var(--app-surface-bg); display:flex; flex-direction:column; gap:0.2rem; box-shadow:0 1px 4px rgba(0,0,0,0.04); transition:transform 0.08s ease, box-shadow 0.08s ease; }
  .target-select-card:hover { transform:translateY(-1px); box-shadow:0 2px 6px rgba(0,0,0,0.06); }
  .target-select-card input { margin-right:0.35rem; }
  .target-select-meta { font-size:0.85rem; color:var(--bs-gray-600); }
</style>

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
document.addEventListener('DOMContentLoaded', function(){
  const withDoubleConfirm = (message) => {
    if (typeof doubleConfirm === 'function') {
      return doubleConfirm(message);
    }
    return confirm(message) && confirm('Please confirm again to proceed.');
  };

  const modalEl = document.getElementById('notificationModal');
  const modal = modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal ? new bootstrap.Modal(modalEl) : null;
  const form = modalEl?.querySelector('form');
  const actionInput = document.getElementById('notificationFormAction');
  const idInput = document.getElementById('notificationId');
  const titleEl = document.getElementById('notificationModalTitle');
  const submitEl = document.getElementById('notificationSubmit');
  const contentField = form?.querySelector('textarea[name="content"]');
  const beginField = form?.querySelector('input[name="valid_begin_date"]');
  const endField = form?.querySelector('input[name="valid_end_date"]');
  const memberCheckboxes = () => Array.from(form?.querySelectorAll('.member-checkbox') || []);

  const setMemberSelections = (ids = []) => {
    const idSet = new Set(ids.map(String));
    memberCheckboxes().forEach(cb => { cb.checked = idSet.has(cb.value); });
  };

  const openCreateModal = () => {
    if(!form) return;
    form.reset();
    actionInput.value = 'create_notification';
    idInput.value = '';
    titleEl.textContent = translations?.[document.documentElement.lang || 'zh']?.['notification_edit.title_add'] || 'Add Notification';
    submitEl.textContent = translations?.[document.documentElement.lang || 'zh']?.['notification_edit.save'] || 'Save';
    setMemberSelections([]);
  };

  modalEl?.addEventListener('hidden.bs.modal', openCreateModal);

  document.querySelector('[data-bs-target="#notificationModal"][data-i18n="notifications.add"]')?.addEventListener('click', openCreateModal);

  document.querySelectorAll('.toggle-members').forEach(btn=>{
    btn.addEventListener('click',()=>{
      const ul=document.getElementById('members-'+btn.dataset.id);
      if(!ul) return;
      const isHidden = ul.style.display==='none';
      ul.style.display=isHidden?'block':'none';
      btn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
      const lang=document.documentElement.lang||'zh';
      const showText = translations?.[lang]?.['notifications.toggle_details'] || btn.textContent;
      const hideText = translations?.[lang]?.['notifications.toggle_hide'] || showText;
      btn.textContent = isHidden ? hideText : showText;
    });
  });

  document.querySelectorAll('.delete-notification').forEach(link=>{
    link.addEventListener('click',e=>{
      const lang=document.documentElement.lang||'zh';
      const msg=translations?.[lang]?.['notifications.confirm.revoke'] || 'Revoke this notification?';
      if(!withDoubleConfirm(msg)) e.preventDefault();
    });
  });

  document.querySelectorAll('.delete-regulation').forEach(link=>{
    link.addEventListener('click',e=>{
      const lang=document.documentElement.lang||'zh';
      const msg=translations?.[lang]?.['regulations.confirm.delete'] || 'Delete this regulation?';
      if(!withDoubleConfirm(msg)) e.preventDefault();
    });
  });

  document.querySelectorAll('.edit-notification').forEach(btn=>{
    btn.addEventListener('click',()=>{
      if(!form || !modal) return;
      form.reset();
      actionInput.value = 'update_notification';
      idInput.value = btn.dataset.id || '';
      if(contentField) contentField.value = btn.dataset.content || '';
      if(beginField) beginField.value = btn.dataset.begin || '';
      if(endField) endField.value = btn.dataset.end || '';
      const members = (()=>{ try { return JSON.parse(btn.dataset.members || '[]'); } catch(e){ return []; }})();
      setMemberSelections(members);
      titleEl.textContent = translations?.[document.documentElement.lang || 'zh']?.['notification_edit.title_edit'] || 'Edit Notification';
      submitEl.textContent = translations?.[document.documentElement.lang || 'zh']?.['notification_edit.save'] || 'Save';
      modal.show();
    });
  });

  const pendingEdit = document.getElementById('notification-page')?.dataset.editId;
  if(pendingEdit){
    const btn=document.querySelector(`.edit-notification[data-id="${pendingEdit}"]`);
    btn?.click();
  }

  const selectAllBtn=document.getElementById('select-all');
  selectAllBtn?.addEventListener('click',()=>{
    const boxes=document.querySelectorAll('.member-checkbox');
    const allChecked=Array.from(boxes).every(cb=>cb.checked);
    boxes.forEach(cb=>cb.checked=!allChecked);
  });

  const expiredToggle=document.getElementById('toggleExpiredNotifications');
  const expiredContainer=document.getElementById('expiredNotifications');
  if(expiredToggle && expiredContainer){
    const updateText=(state)=>{
      const lang=document.documentElement.lang||'zh';
      const key=state==='show'? 'notifications.show_expired' : 'notifications.hide_expired';
      expiredToggle.textContent=translations?.[lang]?.[key] || expiredToggle.textContent;
    };
    expiredContainer.addEventListener('show.bs.collapse',()=>updateText('hide'));
    expiredContainer.addEventListener('hide.bs.collapse',()=>updateText('show'));
    updateText('show');
  }

  document.querySelectorAll('.view-details').forEach(btn=>{
    btn.addEventListener('click',()=>{
      document.getElementById('regDesc').textContent=btn.dataset.desc || '';
      let files=[];
      try{
        files=JSON.parse(btn.dataset.files || '[]');
      }catch(err){
        files=[];
      }
      const list=document.getElementById('regFiles');
      if(list){
        list.innerHTML='';
        files.forEach(f=>{
          const li=document.createElement('li');
          li.className='list-group-item';
          const a=document.createElement('a');
          a.href='regulation_file.php?id='+f.id;
          a.textContent=f.original_filename;
          a.target='_blank';
          li.appendChild(a);
          list.appendChild(li);
        });
      }
      const modalEl = document.getElementById('regDetailModal');
      if(modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal){
        new bootstrap.Modal(modalEl).show();
      }
    });
  });

  const regulationList=document.getElementById('regulationList');
  if(regulationList && typeof Sortable !== 'undefined'){
    Sortable.create(regulationList, {
      handle: '.drag-handle',
      animation: 150,
      onEnd: function(){
        const order = Array.from(regulationList.querySelectorAll('tr')).map((row,index)=>({id:row.dataset.id, position:index}));
        fetch('regulation_order.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order:order})});
      }
    });
  }
});
</script>

<?php include 'footer.php'; ?>
