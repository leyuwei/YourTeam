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
$affairs_stmt = $pdo->prepare('SELECT a.*, m.name, m.campus_id FROM task_affairs a JOIN members m ON a.member_id=m.id WHERE a.task_id=? ORDER BY a.start_time DESC');
$affairs_stmt->execute([$task_id]);
$affairs = $affairs_stmt->fetchAll();
$members = $pdo->query('SELECT id, campus_id, name FROM members ORDER BY name')->fetchAll();
?>
<h2>Urgent Affairs - <?php echo htmlspecialchars($task['title']); ?></h2>
<table class="table table-bordered">
<tr><th>Description</th><th>Member</th><th>Start</th><th>End</th><th>Action</th></tr>
<?php foreach($affairs as $a): ?>
<tr>
  <td><?= htmlspecialchars($a['description']); ?></td>
  <td><?= htmlspecialchars($a['name']); ?> (<?= htmlspecialchars($a['campus_id']); ?>)</td>
  <td><?= htmlspecialchars($a['start_time']); ?></td>
  <td><?= htmlspecialchars($a['end_time']); ?></td>
  <td><a class="btn btn-sm btn-danger" href="affair_delete.php?id=<?= $a['id']; ?>&task_id=<?= $task_id; ?>" onclick="return doubleConfirm('Delete affair?');">Delete</a></td>
</tr>
<?php endforeach; ?>
</table>
<h4>Add Urgent Affair</h4>
<form method="post" action="affair_add.php">
  <input type="hidden" name="task_id" value="<?= $task_id; ?>">
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="2" required></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Member</label>
    <select name="member_id" class="form-select" required>
      <option value="">Select member</option>
      <?php foreach($members as $m): ?>
      <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['name']); ?> (<?= $m['campus_id']; ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Start Time</label>
    <input type="datetime-local" name="start_time" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">End Time</label>
    <input type="datetime-local" name="end_time" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">Add</button>
  <a href="tasks.php" class="btn btn-secondary">Back</a>
</form>
<?php include 'footer.php'; ?>
