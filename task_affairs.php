<?php
include 'header.php';
$task_id = $_GET['id'] ?? null;
if(!$task_id){
    header('Location: tasks.php');
    exit();
}
$task = $pdo->prepare('SELECT * FROM tasks WHERE id=?');
$task->execute([$task_id]);
$task = $task->fetch();
$affairs_stmt = $pdo->prepare('SELECT a.*, GROUP_CONCAT(CONCAT(m.name, " (", m.campus_id, ")") SEPARATOR ", ") AS members FROM task_affairs a LEFT JOIN task_affair_members am ON a.id=am.affair_id LEFT JOIN members m ON am.member_id=m.id WHERE a.task_id=? GROUP BY a.id ORDER BY a.start_time DESC');
$affairs_stmt->execute([$task_id]);
$affairs = $affairs_stmt->fetchAll();
$members = $pdo->query("SELECT id, campus_id, name FROM members WHERE status != 'exited' ORDER BY name")->fetchAll();
?>
<h2>下辖具体事务 - <?php echo htmlspecialchars($task['title']); ?></h2>
<table class="table table-bordered">
<tr><th>具体事务描述</th><th>负责成员</th><th>起始时间</th><th>结束时间</th><th>操作</th></tr>
<?php foreach($affairs as $a): ?>
<tr>
  <td><?= htmlspecialchars($a['description']); ?></td>
  <td><?= htmlspecialchars($a['members']); ?></td>
  <td><?= htmlspecialchars($a['start_time']); ?></td>
  <td><?= htmlspecialchars($a['end_time']); ?></td>
  <td><a class="btn btn-sm btn-danger" href="affair_delete.php?id=<?= $a['id']; ?>&task_id=<?= $task_id; ?>" onclick="return doubleConfirm('Delete affair?');">Delete</a></td>
</tr>
<?php endforeach; ?>
</table>
<br><br>
<h4>新建具体事务</h4>
<form method="post" action="affair_add.php">
  <input type="hidden" name="task_id" value="<?= $task_id; ?>">
  <div class="mb-3">
    <label class="form-label">具体事务描述</label>
    <textarea name="description" class="form-control" rows="2" required></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">负责成员 (按住Ctrl键点选多个人)</label>
    <select name="member_ids[]" class="form-select" multiple required>
      <?php foreach($members as $m): ?>
      <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['name']); ?> (<?= $m['campus_id']; ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">起始时间</label>
    <input type="datetime-local" name="start_time" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">结束时间</label>
    <input type="datetime-local" name="end_time" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">新增事务</button>
  <a href="tasks.php" class="btn btn-secondary">返回</a>
</form>
<script>
const affairForm = document.querySelector('form[action="affair_add.php"]');
affairForm.addEventListener('submit', function(e){
  const start = affairForm.querySelector('input[name="start_time"]').value;
  const end = affairForm.querySelector('input[name="end_time"]').value;
  if(start && end && new Date(end) <= new Date(start)){
    alert('结束时间必须晚于起始时间');
    e.preventDefault();
  }
});
</script>
<?php include 'footer.php'; ?>
