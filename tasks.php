<?php include 'header.php';
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
  <h2>任务指派 / Task Assign</h2>
  <a class="btn btn-success" href="task_edit.php">Add Task</a>
</div>
<form class="row g-3 mb-3" method="get">
  <div class="col-auto">
    <select name="status" class="form-select">
      <option value="">所有状态</option>
      <option value="active" <?= $status=='active'?'selected':''; ?>>进行中</option>
      <option value="paused" <?= $status=='paused'?'selected':''; ?>>暂停</option>
      <option value="finished" <?= $status=='finished'?'selected':''; ?>>已结束</option>
    </select>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-primary">筛选</button>
  </div>
</form>
<table class="table table-bordered">
<tr><th>任务标题</th><th>开始日期</th><th>状态</th><th>操作</th></tr>
<?php foreach($tasks as $t): ?>
<tr>
  <td><?= htmlspecialchars($t['title']); ?></td>
  <td><?= htmlspecialchars($t['start_date']); ?></td>
  <td><?= htmlspecialchars($t['status']); ?></td>
  <td>
    <a class="btn btn-sm btn-primary" href="task_edit.php?id=<?= $t['id']; ?>">编辑信息</a>
    <a class="btn btn-sm btn-warning" href="task_affairs.php?id=<?= $t['id']; ?>">下辖紧急事务</a>
    <a class="btn btn-sm btn-danger" href="task_delete.php?id=<?= $t['id']; ?>" onclick="return doubleConfirm('Delete task?');">删除</a>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php include 'footer.php'; ?>
