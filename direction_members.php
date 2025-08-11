<?php
include 'header.php';
$direction_id = $_GET['id'] ?? null;
if(!$direction_id){
    header('Location: directions.php');
    exit();
}
$direction_stmt = $pdo->prepare('SELECT * FROM research_directions WHERE id=?');
$direction_stmt->execute([$direction_id]);
$direction = $direction_stmt->fetch();
$current_stmt = $pdo->prepare('SELECT m.id, m.campus_id, m.name FROM direction_members dm JOIN members m ON dm.member_id=m.id WHERE dm.direction_id=?');
$current_stmt->execute([$direction_id]);
$current_members = $current_stmt->fetchAll();
$members = $pdo->query('SELECT id, campus_id, name FROM members ORDER BY name')->fetchAll();
?>
<h2>Direction Members - <?= htmlspecialchars($direction['title']); ?></h2>
<table class="table table-bordered">
<tr><th>Campus ID</th><th>Name</th><th>Action</th></tr>
<?php foreach($current_members as $c): ?>
<tr>
  <td><?= htmlspecialchars($c['campus_id']); ?></td>
  <td><?= htmlspecialchars($c['name']); ?></td>
  <td><a class="btn btn-sm btn-danger" href="direction_member_remove.php?direction_id=<?= $direction_id; ?>&member_id=<?= $c['id']; ?>" onclick="return doubleConfirm('Remove member from direction?');">Remove</a></td>
</tr>
<?php endforeach; ?>
</table>
<h4>Add Member</h4>
<form method="post" action="direction_member_add.php">
  <input type="hidden" name="direction_id" value="<?= $direction_id; ?>">
  <div class="mb-3">
    <label class="form-label">Member</label>
    <select name="member_id" class="form-select" required>
      <option value="">Select member</option>
      <?php foreach($members as $m): ?>
      <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['name']); ?> (<?= $m['campus_id']; ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="submit" class="btn btn-primary">Add</button>
  <a href="directions.php" class="btn btn-secondary">Back</a>
</form>
<?php include 'footer.php'; ?>
