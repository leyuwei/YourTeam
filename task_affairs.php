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
<tr><th>具体事务描述</th><th>负责成员</th><th>起始日期</th><th>结束日期</th><th>天数</th><th>操作</th></tr>
<?php foreach($affairs as $a): ?>
<?php $days = (strtotime($a['end_time']) - strtotime($a['start_time']))/86400; ?>
<tr>
  <td><?= htmlspecialchars($a['description']); ?></td>
  <td><?= htmlspecialchars($a['members']); ?></td>
  <td><?= htmlspecialchars(date('Y-m-d', strtotime($a['start_time']))); ?></td>
  <td><?= htmlspecialchars(date('Y-m-d', strtotime($a['end_time'] . ' -1 day'))); ?></td>
  <td><?= htmlspecialchars($days); ?></td>
  <td>
    <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#editModal<?= $a['id']; ?>">编辑</button>
    <a class="btn btn-sm btn-danger" href="affair_delete.php?id=<?= $a['id']; ?>&task_id=<?= $task_id; ?>" onclick="return doubleConfirm('Delete affair?');">删除</a>
  </td>
</tr>
<?php endforeach; ?>
</table>

<?php foreach($affairs as $a): ?>
<div class="modal fade" id="editModal<?= $a['id']; ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="affair_edit.php" class="edit-affair-form">
        <div class="modal-header">
          <h5 class="modal-title">编辑事务</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $a['id']; ?>">
          <input type="hidden" name="task_id" value="<?= $task_id; ?>">
          <div class="mb-3">
            <label class="form-label">具体事务描述</label>
            <textarea name="description" class="form-control" rows="2" required><?= htmlspecialchars($a['description']); ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">起始日期</label>
            <input type="date" name="start_time" class="form-control edit-start" required value="<?= date('Y-m-d', strtotime($a['start_time'])); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">结束日期</label>
            <input type="date" name="end_time" class="form-control edit-end" required value="<?= date('Y-m-d', strtotime($a['end_time'] . ' -1 day')); ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">保存</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>
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
    <select name="member_ids[]" class="form-select" multiple required size="10">
      <?php foreach($members as $m): ?>
      <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['name']); ?> (<?= $m['campus_id']; ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">起始日期</label>
    <input type="date" name="start_time" id="startDate" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">结束日期</label>
    <input type="date" name="end_time" id="endDate" class="form-control" required>
    <div id="dayCount" class="mt-2"></div>
  </div>
  <button type="submit" class="btn btn-primary">新增事务</button>
  <a href="tasks.php" class="btn btn-secondary">返回</a>
</form>
<script>
const affairForm = document.querySelector('form[action="affair_add.php"]');
const startField = document.getElementById('startDate');
const endField = document.getElementById('endDate');
const dayCount = document.getElementById('dayCount');
function updateDays(){
  if(startField.value && endField.value){
    const start = new Date(startField.value);
    const end = new Date(endField.value);
    const diff = Math.floor((end - start) / (1000*60*60*24)) + 1;
    if(diff <= 0){
      alert('结束日期必须不早于起始日期');
      endField.value = '';
      dayCount.textContent = '';
      return false;
    }
    dayCount.textContent = `本次事务工作量：${diff} 天`;
  } else {
    dayCount.textContent = '';
  }
  return true;
}
startField.addEventListener('change', updateDays);
endField.addEventListener('change', updateDays);
affairForm.addEventListener('submit', function(e){
  if(!updateDays()){
    e.preventDefault();
  }
});

document.querySelectorAll('.edit-affair-form').forEach(function(form){
  form.addEventListener('submit', function(e){
    const s = form.querySelector('.edit-start').value;
    const ed = form.querySelector('.edit-end').value;
    if(s && ed && new Date(ed) < new Date(s)){
      e.preventDefault();
      alert('结束日期必须不早于起始日期');
    }
  });
});
</script>
<?php include 'footer.php'; ?>
