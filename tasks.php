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
  <h2>Small Tasks</h2>
  <a class="btn btn-success" href="task_edit.php">Add Task</a>
</div>
<form class="row g-3 mb-3" method="get">
  <div class="col-auto">
    <select name="status" class="form-select">
      <option value="">All Status</option>
      <option value="active" <?= $status=='active'?'selected':''; ?>>Active</option>
      <option value="paused" <?= $status=='paused'?'selected':''; ?>>Paused</option>
      <option value="finished" <?= $status=='finished'?'selected':''; ?>>Finished</option>
    </select>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-primary">Filter</button>
  </div>
</form>
<table class="table table-bordered">
<tr><th>Title</th><th>Start Date</th><th>Status</th><th>Actions</th></tr>
<?php foreach($tasks as $t): ?>
<tr>
  <td><?= htmlspecialchars($t['title']); ?></td>
  <td><?= htmlspecialchars($t['start_date']); ?></td>
  <td><?= htmlspecialchars($t['status']); ?></td>
  <td>
    <a class="btn btn-sm btn-primary" href="task_edit.php?id=<?= $t['id']; ?>">Edit</a>
    <a class="btn btn-sm btn-warning" href="task_affairs.php?id=<?= $t['id']; ?>">Urgent Affairs</a>
    <a class="btn btn-sm btn-danger" href="task_delete.php?id=<?= $t['id']; ?>" onclick="return confirm('Delete task?');">Delete</a>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php include 'footer.php'; ?>
