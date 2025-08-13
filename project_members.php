<?php
include 'header.php';
$project_id = $_GET['id'] ?? null;
if(!$project_id){
    header('Location: projects.php');
    exit();
}
$project = $pdo->prepare('SELECT * FROM projects WHERE id=?');
$project->execute([$project_id]);
$project = $project->fetch();
$active = $pdo->prepare('SELECT l.id, m.campus_id, m.name, l.join_time FROM project_member_log l JOIN members m ON l.member_id=m.id WHERE l.project_id=? AND l.exit_time IS NULL ORDER BY l.sort_order');
$active->execute([$project_id]);
$active_members = $active->fetchAll();
$logs = $pdo->prepare('SELECT l.*, m.name, m.campus_id FROM project_member_log l JOIN members m ON l.member_id=m.id WHERE l.project_id=? ORDER BY l.join_time');
$logs->execute([$project_id]);
$logs = $logs->fetchAll();
$members = $pdo->query("SELECT id, campus_id, name FROM members WHERE status != 'exited' ORDER BY name")->fetchAll();
?>
<h2>项目成员 - <?php echo htmlspecialchars($project['title']); ?></h2>
<h4>当前成员</h4>
<table class="table table-bordered">
<tr><th></th><th>一卡通号</th><th>姓名</th><th>入项日期</th><th>操作</th></tr>
<tbody id="memberList">
<?php foreach($active_members as $a): ?>
<tr data-id="<?= $a['id']; ?>">
  <td class="drag-handle">&#9776;</td>
  <td><?= htmlspecialchars($a['campus_id']); ?></td>
  <td><?= htmlspecialchars($a['name']); ?></td>
  <td><?= htmlspecialchars($a['join_time']); ?></td>
  <td><a class="btn btn-sm btn-danger" href="project_member_remove.php?log_id=<?= $a['id']; ?>&project_id=<?= $project_id; ?>" onclick="return doubleConfirm('Remove member from project?');">Remove</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<br><br>
<h4>新增成员</h4>
<form method="post" action="project_member_add.php">
  <input type="hidden" name="project_id" value="<?= $project_id; ?>">
  <div class="mb-3">
    <label class="form-label">成员</label>
    <select name="member_id" class="form-select" required>
      <option value="">选择成员</option>
      <?php foreach($members as $m): ?>
      <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['name']); ?> (<?= $m['campus_id']; ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">入项日期</label>
    <input type="date" name="join_time" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">新增</button>
  <a href="projects.php" class="btn btn-secondary">返回</a>
</form>
<br>
<h4 class="mt-5">成员变动历史</h4>
<table class="table table-bordered">
<tr><th>成员</th><th>入项日期</th><th>退出日期</th></tr>
<?php foreach($logs as $l): ?>
<tr>
  <td><?= htmlspecialchars($l['name']); ?> (<?= htmlspecialchars($l['campus_id']); ?>)</td>
  <td><?= htmlspecialchars($l['join_time']); ?></td>
  <td><?= htmlspecialchars($l['exit_time']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  Sortable.create(document.getElementById('memberList'), {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function() {
      const order = Array.from(document.querySelectorAll('#memberList tr')).map((row, index) => ({id: row.dataset.id, position: index}));
      fetch('project_member_order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({order: order})
      });
    }
  });
});
</script>
<?php include 'footer.php'; ?>
