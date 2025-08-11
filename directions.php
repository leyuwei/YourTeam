<?php include 'header.php';
$directions = $pdo->query('SELECT * FROM research_directions ORDER BY id DESC')->fetchAll();
?>
<div class="d-flex justify-content-between mb-3">
  <h2>Research Directions</h2>
  <div>
    <a class="btn btn-success" href="direction_edit.php">Add Direction</a>
  </div>
</div>
<table class="table table-bordered">
<tr><th>Title</th><th>Actions</th></tr>
<?php foreach($directions as $d): ?>
<tr>
  <td><?= htmlspecialchars($d['title']); ?></td>
  <td>
    <a class="btn btn-sm btn-primary" href="direction_edit.php?id=<?= $d['id']; ?>">Edit</a>
    <a class="btn btn-sm btn-warning" href="direction_members.php?id=<?= $d['id']; ?>">Members</a>
    <a class="btn btn-sm btn-danger" href="direction_delete.php?id=<?= $d['id']; ?>" onclick="return doubleConfirm('Delete direction?');">Delete</a>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php include 'footer.php'; ?>
