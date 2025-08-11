<?php
include 'header.php';

// Determine sorting column and direction from query parameters
$columns = [
    'campus_id' => 'Campus ID',
    'name' => 'Name',
    'email' => 'Email',
    'identity_number' => 'Identity No',
    'year_of_join' => 'Year Joined',
    'current_degree' => 'Current Degree',
    'degree_pursuing' => 'Degree Pursuing',
    'phone' => 'Phone',
    'wechat' => 'WeChat',
    'department' => 'Department',
    'workplace' => 'Workplace',
    'homeplace' => 'Homeplace'
];

$sort = $_GET['sort'] ?? 'id';
if (!array_key_exists($sort, $columns) && $sort !== 'id') {
    $sort = 'id';
}
$dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$stmt = $pdo->query("SELECT * FROM members ORDER BY $sort $dir");
$members = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between mb-3">
  <h2>Team Members</h2>
  <div>
    <a class="btn btn-success" href="member_edit.php">Add Member</a>
    <a class="btn btn-secondary" href="members_import.php">Import Excel</a>
    <a class="btn btn-secondary" href="members_export.php">Export Excel</a>
  </div>
</div>
<div class="table-responsive">
<table class="table table-bordered table-striped table-hover">
  <tr>
    <?php foreach($columns as $col => $label):
        $newDir = ($sort === $col && $dir === 'ASC') ? 'desc' : 'asc';
    ?>
      <th><a href="?sort=<?= $col; ?>&amp;dir=<?= $newDir; ?>"><?= htmlspecialchars($label); ?></a></th>
    <?php endforeach; ?>
    <th>Actions</th>
  </tr>
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
</div>
<?php include 'footer.php'; ?>
