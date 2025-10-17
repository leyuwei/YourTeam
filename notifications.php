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
  <button type="button" class="btn btn-success" id="addNotificationBtn" data-i18n="notifications.add">Add Notification</button>
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
      <button type="button" class="btn btn-sm btn-primary btn-edit-notification" data-id="<?= $n['id']; ?>" data-i18n="notifications.action_edit">Edit</button>
      <a class="btn btn-sm btn-danger delete-notification" href="notification_revoke.php?id=<?= $n['id']; ?>" data-i18n="notifications.action_revoke">Revoke</a>
    </td>
  </tr>
<?php endforeach; ?>
</table>

<div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="notificationModalTitle" data-i18n="notification_edit.title_add">Add Notification</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="notificationError"></div>
        <form id="notificationForm">
          <input type="hidden" id="notificationId">
          <div class="mb-3">
            <label class="form-label" data-i18n="notification_edit.label_content">Content</label>
            <textarea class="form-control" id="notificationContent" rows="4" required></textarea>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" data-i18n="notification_edit.label_begin">Begin Date</label>
              <input type="date" class="form-control" id="notificationBegin" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" data-i18n="notification_edit.label_end">End Date</label>
              <input type="date" class="form-control" id="notificationEnd" required>
            </div>
          </div>
          <div class="mt-3">
            <label class="form-label" data-i18n="notification_edit.label_members">Target Members</label>
            <div class="mb-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" id="notificationSelectAll" data-i18n="notification_edit.select_all">Select All</button>
            </div>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3" id="notificationMembers"></div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="notification_edit.cancel">Cancel</button>
        <button type="submit" class="btn btn-primary" form="notificationForm" data-i18n="notification_edit.save">Save</button>
      </div>
    </div>
  </div>
</div>

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
document.addEventListener('DOMContentLoaded', () => {
  const langKey = () => document.documentElement.lang || 'zh';
  const t = (key, fallback = '') => {
    try {
      return translations?.[langKey()]?.[key] ?? fallback;
    } catch (e) {
      return fallback;
    }
  };

  document.querySelectorAll('.toggle-members').forEach(btn => {
    btn.addEventListener('click', () => {
      const ul = document.getElementById('members-' + btn.dataset.id);
      if (!ul) return;
      ul.style.display = ul.style.display === 'none' ? 'block' : 'none';
    });
  });

  document.querySelectorAll('.delete-notification').forEach(link => {
    link.addEventListener('click', e => {
      const msg = t('notifications.confirm.revoke', 'Are you sure to revoke this notification?');
      if (!doubleConfirm(msg)) {
        e.preventDefault();
      }
    });
  });

  document.querySelectorAll('.delete-regulation').forEach(link => {
    link.addEventListener('click', e => {
      const msg = t('regulations.confirm.delete', 'Are you sure to delete this regulation?');
      if (!doubleConfirm(msg)) {
        e.preventDefault();
      }
    });
  });

  document.querySelectorAll('.view-details').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('regDesc').textContent = btn.dataset.desc;
      const files = JSON.parse(btn.dataset.files);
      const list = document.getElementById('regFiles');
      list.innerHTML = '';
      files.forEach(f => {
        const li = document.createElement('li');
        li.className = 'list-group-item';
        const a = document.createElement('a');
        a.href = 'regulation_file.php?id=' + f.id;
        a.textContent = f.original_filename;
        li.appendChild(a);
        list.appendChild(li);
      });
      new bootstrap.Modal(document.getElementById('regDetailModal')).show();
    });
  });

  const regulationList = document.getElementById('regulationList');
  if (regulationList) {
    Sortable.create(regulationList, {
      handle: '.drag-handle',
      animation: 150,
      onEnd: function () {
        const order = Array.from(document.querySelectorAll('#regulationList tr')).map((row, index) => ({ id: row.dataset.id, position: index }));
        fetch('regulation_order.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ order }) });
      }
    });
  }

  const notificationModalEl = document.getElementById('notificationModal');
  const notificationModal = notificationModalEl ? new bootstrap.Modal(notificationModalEl) : null;
  const notificationForm = document.getElementById('notificationForm');
  const notificationError = document.getElementById('notificationError');
  const notificationModalTitle = document.getElementById('notificationModalTitle');
  const notificationIdField = document.getElementById('notificationId');
  const notificationContent = document.getElementById('notificationContent');
  const notificationBegin = document.getElementById('notificationBegin');
  const notificationEnd = document.getElementById('notificationEnd');
  const notificationMembersContainer = document.getElementById('notificationMembers');
  const notificationSelectAll = document.getElementById('notificationSelectAll');
  let notificationMembers = [];

  function resetNotificationForm() {
    if (!notificationForm) return;
    notificationForm.reset();
    notificationIdField.value = '';
    notificationMembersContainer.innerHTML = '';
    notificationMembers = [];
    if (notificationError) {
      notificationError.classList.add('d-none');
      notificationError.textContent = '';
    }
  }

  function setNotificationTitle(key) {
    if (!notificationModalTitle) return;
    notificationModalTitle.setAttribute('data-i18n', key);
    window.applyTranslations?.();
  }

  function renderNotificationMembers(selectedIds = []) {
    if (!notificationMembersContainer) return;
    notificationMembersContainer.innerHTML = '';
    if (!notificationMembers.length) {
      const empty = document.createElement('div');
      empty.className = 'text-muted';
      empty.textContent = t('notifications.members.none', 'No members available.');
      notificationMembersContainer.appendChild(empty);
      return;
    }
    notificationMembers.forEach(member => {
      const col = document.createElement('div');
      col.className = 'col';
      const wrapper = document.createElement('div');
      wrapper.className = 'form-check';
      const input = document.createElement('input');
      input.type = 'checkbox';
      input.className = 'form-check-input';
      input.id = 'notificationMember' + member.id;
      input.dataset.memberId = member.id;
      input.checked = selectedIds.includes(member.id);
      const label = document.createElement('label');
      label.className = 'form-check-label';
      label.setAttribute('for', input.id);
      label.textContent = member.name;
      wrapper.appendChild(input);
      wrapper.appendChild(label);
      col.appendChild(wrapper);
      notificationMembersContainer.appendChild(col);
    });
  }

  function openNotificationModal(id = null) {
    if (!notificationModal) return;
    resetNotificationForm();
    setNotificationTitle(id ? 'notification_edit.title_edit' : 'notification_edit.title_add');
    const url = id ? `notification_edit.php?id=${id}` : 'notification_edit.php';
    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(resp => resp.json())
      .then(data => {
        if (!data.success) {
          throw new Error('load');
        }
        const info = data.notification || {};
        notificationIdField.value = info.id || '';
        notificationContent.value = info.content || '';
        notificationBegin.value = info.valid_begin_date || '';
        notificationEnd.value = info.valid_end_date || '';
        notificationMembers = data.members || [];
        renderNotificationMembers(info.members || []);
        notificationModal.show();
        window.applyTranslations?.();
      })
      .catch(() => {
        if (notificationError) {
          notificationError.textContent = t('notification_edit.load_failed', 'Failed to load notification.');
          notificationError.classList.remove('d-none');
        }
        notificationModal.show();
      });
  }

  const addNotificationBtn = document.getElementById('addNotificationBtn');
  if (addNotificationBtn) {
    addNotificationBtn.addEventListener('click', () => openNotificationModal());
  }

  document.querySelectorAll('.btn-edit-notification').forEach(btn => {
    btn.addEventListener('click', () => openNotificationModal(btn.dataset.id));
  });

  notificationSelectAll?.addEventListener('click', () => {
    const boxes = notificationMembersContainer.querySelectorAll('input[type="checkbox"]');
    const allChecked = boxes.length > 0 && Array.from(boxes).every(cb => cb.checked);
    boxes.forEach(cb => {
      cb.checked = !allChecked;
    });
  });

  notificationForm?.addEventListener('submit', ev => {
    ev.preventDefault();
    if (notificationError) {
      notificationError.classList.add('d-none');
      notificationError.textContent = '';
    }
    const selected = Array.from(notificationMembersContainer.querySelectorAll('input[type="checkbox"]:checked')).map(cb => parseInt(cb.dataset.memberId, 10));
    const payload = {
      id: notificationIdField.value || undefined,
      content: notificationContent.value,
      valid_begin_date: notificationBegin.value,
      valid_end_date: notificationEnd.value,
      members: selected
    };
    fetch('notification_edit.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(resp => resp.json())
      .then(data => {
        if (!data.success) {
          throw new Error(data.message || 'save');
        }
        notificationModal.hide();
        window.location.reload();
      })
      .catch(err => {
        if (!notificationError) return;
        const message = err.message === 'save' ? t('notification_edit.error_generic', 'Failed to save notification.') : err.message;
        notificationError.textContent = message;
        notificationError.classList.remove('d-none');
      });
  });
});
</script>

<?php include 'footer.php'; ?>
