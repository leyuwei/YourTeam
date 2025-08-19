<?php
include 'auth.php';
include 'header.php';
$is_manager = ($_SESSION['role'] === 'manager');
$status = $_GET['status'] ?? '';
if($status){
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE status=? ORDER BY id DESC');
    $stmt->execute([$status]);
    $tasks = $stmt->fetchAll();
} else {
    $tasks = $pdo->query('SELECT * FROM tasks ORDER BY id DESC')->fetchAll();
}
?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="bold-target" data-i18n="tasks.title">Tasks Assignment</h2>
  <?php if($is_manager): ?>
  <a class="btn btn-success" href="task_edit.php" data-i18n="tasks.add">New Task</a>
  <?php endif; ?>
</div>
<form class="row g-3 mb-3" method="get">
  <div class="col-auto">
    <select name="status" class="form-select">
      <option value="" data-i18n="tasks.filter_all">All Status</option>
      <option value="active" <?= $status=='active'?'selected':''; ?> data-i18n="tasks.filter.active">Active</option>
      <option value="paused" <?= $status=='paused'?'selected':''; ?> data-i18n="tasks.filter.paused">Paused</option>
      <option value="finished" <?= $status=='finished'?'selected':''; ?> data-i18n="tasks.filter.finished">Finished</option>
    </select>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-primary" data-i18n="tasks.filter.button">Filter</button>
  </div>
</form>
<div class="form-check form-switch mb-3">
  <input class="form-check-input" type="checkbox" id="boldToggle">
  <label class="form-check-label" for="boldToggle" data-i18n="bold_font">Bold font</label>
</div>
<table class="table table-bordered">
<tr><th data-i18n="tasks.table_title">Title</th><th data-i18n="tasks.table_start">Start</th><th data-i18n="tasks.table_status">Status</th><th data-i18n="tasks.table_actions">Actions</th></tr>
<?php foreach($tasks as $t): ?>
<tr>
  <td class="bold-target"><?= htmlspecialchars($t['title']); ?></td>
  <td><?= htmlspecialchars($t['start_date']); ?></td>
  <td data-i18n="tasks.status.<?= htmlspecialchars($t['status']); ?>"><?= htmlspecialchars($t['status']); ?></td>
  <td>
    <?php if($is_manager): ?>
    <a class="btn btn-sm btn-primary" href="task_edit.php?id=<?= $t['id']; ?>" data-i18n="tasks.action_edit">Edit</a>
    <?php endif; ?>
    <a class="btn btn-sm btn-warning" href="task_affairs.php?id=<?= $t['id']; ?>" data-i18n="tasks.action_affairs">Affairs</a>
    <button type="button" class="btn btn-sm btn-info qr-btn" data-url="task_member_fill.php?task_id=<?= $t['id']; ?>" data-i18n="tasks.action_fill">Self Fill</button>
    <?php if($is_manager): ?>
    <a class="btn btn-sm btn-danger delete-task" href="task_delete.php?id=<?= $t['id']; ?>" data-i18n="tasks.action_delete">Delete</a>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</table>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.delete-task').forEach(link=>{
    link.addEventListener('click',e=>{
      const lang=document.documentElement.lang||'en';
      const msg=translations[lang]['tasks.confirm.delete'];
      if(!doubleConfirm(msg)) e.preventDefault();
    });
  });

  document.getElementById('boldToggle').addEventListener('change', function(){
    document.querySelectorAll('.bold-target').forEach(el => {
      el.classList.toggle('fw-bold', this.checked);
    });
  });
});
</script>
<?php include 'footer.php'; ?>
