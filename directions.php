<?php include 'header.php';
// Fetch research directions along with their members' names
$stmt = $pdo->query("SELECT d.*, GROUP_CONCAT(m.name ORDER BY dm.sort_order SEPARATOR ', ') AS member_names
                     FROM research_directions d
                     LEFT JOIN direction_members dm ON d.id = dm.direction_id
                     LEFT JOIN members m ON dm.member_id = m.id
                     GROUP BY d.id
                     ORDER BY d.sort_order");
$directions = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between mb-3">
  <h2 data-i18n="directions.title">Research Directions</h2>
  <div>
    <a class="btn btn-success" href="direction_edit.php" data-i18n="directions.add">Add Direction</a>
  </div>
</div>
<table class="table table-bordered">
  <thead>
  <tr>
    <th></th>
    <th data-i18n="directions.table_title">Title</th>
    <th data-i18n="directions.table_members">Members</th>
    <th data-i18n="directions.table_actions">Actions</th>
  </tr>
  </thead>
  <tbody id="directionList">
  <?php foreach($directions as $d): ?>
  <tr data-id="<?= $d['id']; ?>">
    <td class="drag-handle">&#9776;</td>
    <td><?= htmlspecialchars($d['title']); ?></td>
    <td><?= $d['member_names'] ? htmlspecialchars($d['member_names']) : '<em data-i18n="directions.none">None</em>'; ?></td>
    <td>
      <a class="btn btn-sm btn-primary" href="direction_edit.php?id=<?= $d['id']; ?>" data-i18n="directions.action_edit">Edit</a>
      <a class="btn btn-sm btn-warning" href="direction_members.php?id=<?= $d['id']; ?>" data-i18n="directions.action_members">Members</a>
      <a class="btn btn-sm btn-danger" href="direction_delete.php?id=<?= $d['id']; ?>" onclick="return doubleConfirm('Delete direction?');" data-i18n="directions.action_delete">Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<h3 class="mt-4">Member Direction Assignments</h3>
<table class="table table-bordered">
  <tr><th>Member</th><th>Research Directions</th></tr>
  <?php
  $memberDirs = $pdo->query('SELECT m.name, GROUP_CONCAT(d.title ORDER BY dm.sort_order SEPARATOR ", ") AS dirs FROM members m LEFT JOIN direction_members dm ON m.id=dm.member_id LEFT JOIN research_directions d ON dm.direction_id=d.id GROUP BY m.id ORDER BY m.sort_order')->fetchAll();
  foreach($memberDirs as $md): ?>
  <tr>
    <td><?= htmlspecialchars($md["name"]); ?></td>
    <td><?= $md['dirs'] ? htmlspecialchars($md['dirs']) : '<em>None</em>'; ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  Sortable.create(document.getElementById('directionList'), {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function(){
      const order = Array.from(document.querySelectorAll('#directionList tr')).map((row, index) => ({id: row.dataset.id, position: index}));
      fetch('direction_order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({order: order})
      });
    }
  });
});
</script>
<?php include 'footer.php'; ?>
