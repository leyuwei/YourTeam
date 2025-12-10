<?php
include 'auth_manager.php';

$id = $_GET['id'] ?? null;
$notification = ['content'=>'','valid_begin_date'=>'','valid_end_date'=>''];
$selected = [];
if($id){
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE id=?');
    $stmt->execute([$id]);
    $notification = $stmt->fetch();
    $stmt = $pdo->prepare('SELECT member_id FROM notification_targets WHERE notification_id=?');
    $stmt->execute([$id]);
    $selected = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $content = $_POST['content'];
    $begin = $_POST['valid_begin_date'];
    $end = $_POST['valid_end_date'];
    $members_selected = $_POST['members'] ?? [];
    if($id){
        $stmt = $pdo->prepare('UPDATE notifications SET content=?, valid_begin_date=?, valid_end_date=? WHERE id=?');
        $stmt->execute([$content,$begin,$end,$id]);
        $pdo->prepare('DELETE FROM notification_targets WHERE notification_id=?')->execute([$id]);
        foreach($members_selected as $m){
            $pdo->prepare('INSERT INTO notification_targets(notification_id,member_id) VALUES(?,?)')->execute([$id,$m]);
        }
    } else {
        $stmt = $pdo->prepare('INSERT INTO notifications(content,valid_begin_date,valid_end_date) VALUES(?,?,?)');
        $stmt->execute([$content,$begin,$end]);
        $nid = $pdo->lastInsertId();
        foreach($members_selected as $m){
            $pdo->prepare('INSERT INTO notification_targets(notification_id,member_id) VALUES(?,?)')->execute([$nid,$m]);
        }
    }
    header('Location: notifications.php');
    exit();
}

$members = $pdo->query("SELECT id,name,department,degree_pursuing,year_of_join FROM members WHERE status='in_work' ORDER BY name")
    ->fetchAll();

include 'header.php';
?>
<style>
  .target-select-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:0.5rem; max-height:380px; overflow:auto; padding:0.5rem; background:var(--app-table-striped-bg); border-radius:0.75rem; border:1px solid var(--app-table-border); }
  .target-select-card { border:1px solid var(--app-table-border); border-radius:0.55rem; padding:0.5rem 0.65rem; background:var(--app-surface-bg); display:flex; flex-direction:column; gap:0.2rem; box-shadow:0 1px 4px rgba(0,0,0,0.04); }
  .target-select-card input { margin-right:0.35rem; }
  .target-select-meta { font-size:0.85rem; color:var(--bs-gray-600); }
</style>
<h2 data-i18n="<?= $id? 'notification_edit.title_edit':'notification_edit.title_add'; ?>"><?= $id? 'Edit Notification':'Add Notification'; ?></h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label" data-i18n="notification_edit.label_content">Content</label>
    <textarea name="content" class="form-control" rows="4" required><?= htmlspecialchars($notification['content']); ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="notification_edit.label_begin">Begin Date</label>
    <input type="date" name="valid_begin_date" class="form-control" value="<?= htmlspecialchars($notification['valid_begin_date']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="notification_edit.label_end">End Date</label>
    <input type="date" name="valid_end_date" class="form-control" value="<?= htmlspecialchars($notification['valid_end_date']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="notification_edit.label_members">Target Members</label>
    <div class="mb-2">
      <button type="button" id="select-all" class="btn btn-sm btn-secondary" data-i18n="notification_edit.select_all">Select All</button>
    </div>
    <div class="target-select-grid">
      <?php foreach($members as $m): ?>
        <label class="target-select-card">
          <div class="d-flex align-items-start">
            <input class="form-check-input mt-1 member-checkbox" type="checkbox" name="members[]" value="<?= $m['id']; ?>" <?= in_array($m['id'],$selected)?'checked':''; ?>>
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
  <button type="submit" class="btn btn-primary" data-i18n="notification_edit.save">Save</button>
  <a href="notifications.php" class="btn btn-secondary" data-i18n="notification_edit.cancel">Cancel</a>
</form>
<script>
document.getElementById('select-all').addEventListener('click', () => {
  const checkboxes = document.querySelectorAll('.member-checkbox');
  const allChecked = Array.from(checkboxes).every(cb => cb.checked);
  checkboxes.forEach(cb => { cb.checked = !allChecked; });
});
</script>
<?php include 'footer.php'; ?>
