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

$sort = $_GET['sort'] ?? 'sort_order';
if (!array_key_exists($sort, $columns) && $sort !== 'sort_order') {
    $sort = 'sort_order';
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
    <a class="btn btn-warning" href="member_self_update.php">Ask for Update</a>
  </div>
</div>
<div class="table-responsive">
<table class="table table-bordered table-striped table-hover">
  <thead>
  <tr>
    <th></th>
    <?php foreach($columns as $col => $label):
        $newDir = ($sort === $col && $dir === 'ASC') ? 'desc' : 'asc';
    ?>
      <th><a href="?sort=<?= $col; ?>&amp;dir=<?= $newDir; ?>"><?= htmlspecialchars($label); ?></a></th>
    <?php endforeach; ?>
    <th>Actions</th>
  </tr>
  </thead>
  <tbody id="memberList">
  <?php foreach($members as $m): ?>
  <tr data-id="<?= $m['id']; ?>">
    <td class="drag-handle">&#9776;</td>
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
      <a class="btn btn-sm btn-danger" href="member_delete.php?id=<?= $m['id']; ?>" onclick="return doubleConfirm('Delete member?');">Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  Sortable.create(document.getElementById('memberList'), {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function(){
      const order = Array.from(document.querySelectorAll('#memberList tr')).map((row, index) => ({id: row.dataset.id, position: index}));
      fetch('member_order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({order: order})
      });
    }
  });
});
</script>
<?php include 'footer.php'; ?>
