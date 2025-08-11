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
$active = $pdo->prepare('SELECT l.id, m.campus_id, m.name, l.join_time FROM project_member_log l JOIN members m ON l.member_id=m.id WHERE l.project_id=? AND l.exit_time IS NULL');
$active->execute([$project_id]);
$active_members = $active->fetchAll();
$logs = $pdo->prepare('SELECT l.*, m.name, m.campus_id FROM project_member_log l JOIN members m ON l.member_id=m.id WHERE l.project_id=? ORDER BY l.join_time');
$logs->execute([$project_id]);
$logs = $logs->fetchAll();
$members = $pdo->query('SELECT id, campus_id, name FROM members ORDER BY name')->fetchAll();
?>
<h2>Project Members - <?php echo htmlspecialchars($project['title']); ?></h2>
<h4>Current Members</h4>
<table class="table table-bordered">
<tr><th>Campus ID</th><th>Name</th><th>Join Time</th><th>Action</th></tr>
<?php foreach($active_members as $a): ?>
<tr>
  <td><?= htmlspecialchars($a['campus_id']); ?></td>
  <td><?= htmlspecialchars($a['name']); ?></td>
  <td><?= htmlspecialchars($a['join_time']); ?></td>
  <td><a class="btn btn-sm btn-danger" href="project_member_remove.php?log_id=<?= $a['id']; ?>&project_id=<?= $project_id; ?>">Remove</a></td>
</tr>
<?php endforeach; ?>
</table>
<h4>Add Member</h4>
<form method="post" action="project_member_add.php">
  <input type="hidden" name="project_id" value="<?= $project_id; ?>">
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
    <label class="form-label">Join Time</label>
    <input type="datetime-local" name="join_time" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">Add</button>
  <a href="projects.php" class="btn btn-secondary">Back</a>
</form>
<h4 class="mt-5">Member History</h4>
<table class="table table-bordered">
<tr><th>Member</th><th>Join Time</th><th>Exit Time</th></tr>
<?php foreach($logs as $l): ?>
<tr>
  <td><?= htmlspecialchars($l['name']); ?> (<?= htmlspecialchars($l['campus_id']); ?>)</td>
  <td><?= htmlspecialchars($l['join_time']); ?></td>
  <td><?= htmlspecialchars($l['exit_time']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php include 'footer.php'; ?>
