<?php
include 'header.php';
$regulations = $pdo->query('SELECT * FROM regulations ORDER BY sort_order')->fetchAll();
$memberLoginSettingsMessageKey = '';
$memberLoginSettingsMessageType = 'success';
$memberLoginFormMethod = 'identity';
$memberAuthSettings = null;
foreach($regulations as &$r){
    $stmt = $pdo->prepare('SELECT id, original_filename FROM regulation_files WHERE regulation_id=?');
    $stmt->execute([$r['id']]);
    $r['files'] = $stmt->fetchAll();
}
unset($r);
if($_SESSION['role']==='member'){
    $member_id = $_SESSION['member_id'];
    if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_member_login'){
        $selectedMethod = (($_POST['member_login_method'] ?? '') === 'password') ? 'password' : 'identity';
        $memberLoginFormMethod = $selectedMethod;
        if($selectedMethod === 'identity'){
            $stmt = $pdo->prepare('UPDATE members SET login_method = ?, password_hash = NULL WHERE id = ?');
            $stmt->execute(['identity', $member_id]);
            $memberLoginSettingsMessageKey = 'member.login_settings.success_identity';
            $memberLoginSettingsMessageType = 'success';
        } elseif($selectedMethod === 'password'){
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            if($newPassword === '' || $confirmPassword === ''){
                $memberLoginSettingsMessageKey = 'member.login_settings.error_password_required';
                $memberLoginSettingsMessageType = 'danger';
            } elseif($newPassword !== $confirmPassword){
                $memberLoginSettingsMessageKey = 'member.login_settings.error_password_mismatch';
                $memberLoginSettingsMessageType = 'danger';
            } else {
                $stmt = $pdo->prepare('UPDATE members SET login_method = ?, password_hash = ? WHERE id = ?');
                $stmt->execute(['password', password_hash($newPassword, PASSWORD_DEFAULT), $member_id]);
                $memberLoginSettingsMessageKey = 'member.login_settings.success_password';
                $memberLoginSettingsMessageType = 'success';
            }
        } else {
            $memberLoginSettingsMessageKey = 'member.login_settings.error_invalid_method';
            $memberLoginSettingsMessageType = 'danger';
        }
    }
    $stmt = $pdo->prepare('SELECT login_method FROM members WHERE id=?');
    $stmt->execute([$member_id]);
    $memberAuthSettings = $stmt->fetch();
    if($memberLoginSettingsMessageType === 'success'){
        $memberLoginFormMethod = is_array($memberAuthSettings) ? ($memberAuthSettings['login_method'] ?? 'identity') : 'identity';
    }
    $pdo->prepare('UPDATE notification_targets nt JOIN notifications n ON nt.notification_id=n.id SET nt.status="seen" WHERE nt.member_id=? AND nt.status="sent" AND n.is_revoked=0 AND CURDATE() BETWEEN n.valid_begin_date AND n.valid_end_date')->execute([$member_id]);
    $stmt = $pdo->prepare('SELECT n.id,n.content,n.valid_begin_date,n.valid_end_date,nt.status FROM notifications n JOIN notification_targets nt ON n.id=nt.notification_id WHERE nt.member_id=? AND n.is_revoked=0 AND CURDATE() BETWEEN n.valid_begin_date AND n.valid_end_date ORDER BY CASE nt.status WHEN \'checked\' THEN 1 ELSE 0 END, n.id DESC');
    $stmt->execute([$member_id]);
    $notifications = $stmt->fetchAll();
    $pendingNotifications = array_filter($notifications, fn($n) => $n['status'] !== 'checked');
}
?>

<?php if($_SESSION['role']==='member'): ?>
<div class="alert alert-info d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4" id="memberLoginSettingsSummary">
  <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
    <span class="fw-semibold" data-i18n="member.login_settings.current_label">Current login method:</span>
    <?php if($memberAuthSettings): ?>
    <span class="badge rounded-pill bg-primary" id="memberLoginCurrentBadge" data-i18n="member.login_settings.current.<?= htmlspecialchars($memberAuthSettings['login_method'] ?? 'identity'); ?>">Current method: Identity Number</span>
    <?php else: ?>
    <span class="badge rounded-pill bg-primary" id="memberLoginCurrentBadge" data-i18n="member.login_settings.current.identity">Current method: Identity Number</span>
    <?php endif; ?>
  </div>
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#memberLoginSettingsModal" data-i18n="member.login_settings.manage_button">Manage Login Method</button>
</div>
<?php if($memberLoginSettingsMessageKey): ?>
<div class="alert alert-<?= htmlspecialchars($memberLoginSettingsMessageType); ?> mb-4" id="memberLoginSettingsFeedback" data-i18n="<?= $memberLoginSettingsMessageKey; ?>"></div>
<?php endif; ?>

<div class="modal fade" id="memberLoginSettingsModal" tabindex="-1" aria-labelledby="memberLoginSettingsTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="memberLoginSettingsTitle" data-i18n="member.login_settings.title">Login Preferences</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3" data-i18n="member.login_settings.description">Choose how you would like to sign in as a member.</p>
        <form method="post" id="memberLoginSettingsForm" class="row g-3">
          <input type="hidden" name="action" value="update_member_login">
          <div class="col-12">
            <label class="form-label fw-semibold" data-i18n="member.login_settings.method_label">Preferred login method</label>
            <div class="btn-group" role="group" aria-label="Member login preference">
              <input type="radio" class="btn-check" name="member_login_method" id="memberSettingLoginIdentity" value="identity" <?= $memberLoginFormMethod === 'password' ? '' : 'checked'; ?>>
              <label class="btn btn-outline-primary" for="memberSettingLoginIdentity" data-i18n="member.login_settings.method.identity">Identity Number</label>
              <input type="radio" class="btn-check" name="member_login_method" id="memberSettingLoginPassword" value="password" <?= $memberLoginFormMethod === 'password' ? 'checked' : ''; ?>>
              <label class="btn btn-outline-primary" for="memberSettingLoginPassword" data-i18n="member.login_settings.method.password">Password</label>
            </div>
            <div class="form-text" id="memberLoginPreferenceHint" data-i18n="<?= $memberLoginFormMethod === 'password' ? 'member.login_settings.method.password_hint' : 'member.login_settings.method.identity_hint'; ?>"></div>
          </div>
          <div id="memberPasswordPreferenceFields" class="col-12 <?= $memberLoginFormMethod === 'password' ? '' : 'd-none'; ?>">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" data-i18n="member.login_settings.password.label">New Password</label>
                <input type="password" name="new_password" class="form-control" data-i18n-placeholder="member.login_settings.password.placeholder" placeholder="Enter new password">
              </div>
              <div class="col-md-6">
                <label class="form-label" data-i18n="member.login_settings.password.confirm">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" data-i18n-placeholder="member.login_settings.password.confirm_placeholder" placeholder="Confirm password">
              </div>
            </div>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary" data-i18n="member.login_settings.submit">Save Preference</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<style>
  .notification-item.pending {
    border-left: 4px solid var(--app-highlight-border);
    background: var(--app-highlight-surface);
  }
  .notification-item.pending .notification-content {
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--app-highlight-text);
  }
</style>
<h2 class="mt-4 mb-3" data-i18n="index.notifications" style="font-weight:bold; color:red; font-color:red">Notifications</h2>
<div class="list-group mb-4">
  <?php foreach($notifications as $n): ?>
  <div class="list-group-item notification-item <?= $n['status']!=='checked' ? 'pending' : ''; ?>">
    <div class="d-flex w-100 justify-content-between">
      <p class="mb-1 notification-content<?= $n['status']!=='checked' ? '' : ' text-body'; ?>"><?= nl2br(htmlspecialchars($n['content'])); ?></p>
      <small><?= htmlspecialchars($n['valid_begin_date']); ?> ~ <?= htmlspecialchars($n['valid_end_date']); ?></small>
    </div>
    <div class="mt-2">
      <span class="badge <?= $n['status']!=='checked' ? 'bg-warning text-dark' : 'bg-secondary'; ?> me-2" data-i18n="notifications.status.<?= $n['status']; ?>"><?= $n['status']; ?></span>
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
<?php if(!empty($pendingNotifications)): ?>
<div class="modal fade" id="pendingNotificationsModal" tabindex="-1" aria-hidden="true" aria-labelledby="pendingNotificationsTitle" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="pendingNotificationsTitle" data-i18n="index.pending_notifications.title">Pending Notifications</h5>
      </div>
      <div class="modal-body">
        <p class="text-muted" data-i18n="index.pending_notifications.description">You still have notifications that need your attention.</p>
        <div class="list-group">
          <?php foreach($pendingNotifications as $pn): ?>
          <div class="list-group-item notification-item pending">
            <div class="d-flex w-100 justify-content-between">
              <p class="mb-1 notification-content"><?= nl2br(htmlspecialchars($pn['content'])); ?></p>
              <small><?= htmlspecialchars($pn['valid_begin_date']); ?> ~ <?= htmlspecialchars($pn['valid_end_date']); ?></small>
            </div>
            <div class="mt-2">
              <span class="badge bg-warning text-dark me-2" data-i18n="notifications.status.<?= $pn['status']; ?>"><?= $pn['status']; ?></span>
              <a class="btn btn-sm btn-outline-success check-notification" href="notification_check.php?id=<?= $pn['id']; ?>" data-i18n="notifications.action_check">Check</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-i18n="index.pending_notifications.maybe_later">Maybe Later</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<script>
document.querySelectorAll('.check-notification').forEach(link=>{
  link.addEventListener('click',e=>{
    const lang=document.documentElement.lang||'zh';
    const msg=translations[lang]['notifications.confirm.check'];
    if(!confirm(msg)) e.preventDefault();
  });
});
</script>
<?php if(!empty($pendingNotifications)): ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const modalEl=document.getElementById('pendingNotificationsModal');
  if(modalEl){
    const reminderModal=new bootstrap.Modal(modalEl,{backdrop:'static',keyboard:false});
    reminderModal.show();
  }
});
</script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const preferenceRadios=document.querySelectorAll('input[name="member_login_method"]');
  const passwordFields=document.getElementById('memberPasswordPreferenceFields');
  const hint=document.getElementById('memberLoginPreferenceHint');
  const modalEl=document.getElementById('memberLoginSettingsModal');
  const updatePreferenceUI=()=>{
    const selected=document.querySelector('input[name="member_login_method"]:checked');
    const method=selected ? selected.value : 'identity';
    if(passwordFields){
      const isPassword=method==='password';
      passwordFields.classList.toggle('d-none', !isPassword);
      passwordFields.querySelectorAll('input').forEach(input=>{
        input.disabled=!isPassword;
        if(!isPassword){ input.value=''; }
      });
    }
    if(hint){
      const hintKey=method==='password' ? 'member.login_settings.method.password_hint' : 'member.login_settings.method.identity_hint';
      hint.setAttribute('data-i18n', hintKey);
    }
    if(typeof applyTranslations === 'function'){
      applyTranslations();
    }
  };
  preferenceRadios.forEach(radio=>radio.addEventListener('change', updatePreferenceUI));
  updatePreferenceUI();

  const settingsState=<?php echo json_encode([
    'hasMessage'=>(bool)$memberLoginSettingsMessageKey,
    'messageType'=>$memberLoginSettingsMessageType,
    'formMethod'=>$memberLoginFormMethod
  ]); ?>;
  if(settingsState.hasMessage && settingsState.messageType==='danger' && modalEl){
    const modalInstance=bootstrap.Modal.getOrCreateInstance(modalEl,{backdrop:'static'});
    modalInstance.show();
  }
  if(passwordFields){
    const inputs=passwordFields.querySelectorAll('input');
    const isPassword=<?php echo json_encode($memberLoginFormMethod === 'password'); ?>;
    inputs.forEach(input=>{ input.disabled=!isPassword; });
  }
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
<br>

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
