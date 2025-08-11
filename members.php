<?php include 'header.php';
$members = $pdo->query('SELECT * FROM members ORDER BY id')->fetchAll();
?>
<div class="d-flex justify-content-between mb-3">
  <h2>Team Members</h2>
  <div>
    <a class="btn btn-success" href="member_edit.php">Add Member</a>
    <a class="btn btn-secondary" href="members_import.php">Import Excel</a>
    <a class="btn btn-secondary" href="members_export.php">Export Excel</a>
  </div>
</div>
<table class="table table-bordered table-striped">
  <tr><th>Campus ID</th><th>Name</th><th>Email</th><th>Identity No</th><th>Year Joined</th><th>Current Degree</th><th>Degree Pursuing</th><th>Phone</th><th>WeChat</th><th>Department</th><th>Workplace</th><th>Homeplace</th><th>Actions</th></tr>
  <?php foreach($members as $m): ?>
  <tr>
    <td><?= htmlspecialchars($m['campus_id']); ?></td>
    <td><?= htmlspecialchars($m['name']); ?></td>
    <td><?= htmlspecialchars($m['email']); ?></td>
    <td><?= htmlspecialchars($m['identity_number']); ?></td>
    <td><?= htmlspecialchars($m['year_of_join']); ?></td>
    <td><?= htmlspecialchars($m['current_degree']); ?></td>
    <td><?= htmlspecialchars($m['degree_pursuing']); ?></td>
    <td><?= htmlspecialchars($m['phone']); ?></td>
    <td><?= htmlspecialchars($m['wechat']); ?></td>
    <td><?= htmlspecialchars($m['department']); ?></td>
    <td><?= htmlspecialchars($m['workplace']); ?></td>
    <td><?= htmlspecialchars($m['homeplace']); ?></td>
    <td>
      <a class="btn btn-sm btn-primary" href="member_edit.php?id=<?= $m['id']; ?>">Edit</a>
      <a class="btn btn-sm btn-danger" href="member_delete.php?id=<?= $m['id']; ?>" onclick="return confirm('Delete member?');">Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php include 'footer.php'; ?>
