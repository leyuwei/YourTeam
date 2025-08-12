<?php include 'header.php';
// Fetch research directions along with their members' names
$stmt = $pdo->query("SELECT d.*, GROUP_CONCAT(m.name ORDER BY dm.sort_order SEPARATOR ', ') AS member_names
                     FROM research_directions d
                     LEFT JOIN direction_members dm ON d.id = dm.direction_id
                     LEFT JOIN members m ON dm.member_id = m.id
                     GROUP BY d.id
                     ORDER BY d.id DESC");
$directions = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between mb-3">
  <h2 data-i18n="directions.title">Research Directions</h2>
  <div>
    <a class="btn btn-success" href="direction_edit.php" data-i18n="directions.add">Add Direction</a>
  </div>
</div>
<table class="table table-bordered">
<tr>
  <th data-i18n="directions.table_title">Title</th>
  <th data-i18n="directions.table_members">Members</th>
  <th data-i18n="directions.table_actions">Actions</th>
</tr>
<?php foreach($directions as $d): ?>
<tr>
  <td><?= htmlspecialchars($d['title']); ?></td>
  <td><?= $d['member_names'] ? htmlspecialchars($d['member_names']) : '<em data-i18n="directions.none">None</em>'; ?></td>
  <td>
    <a class="btn btn-sm btn-primary" href="direction_edit.php?id=<?= $d['id']; ?>" data-i18n="directions.action_edit">Edit</a>
    <a class="btn btn-sm btn-warning" href="direction_members.php?id=<?= $d['id']; ?>" data-i18n="directions.action_members">Members</a>
    <a class="btn btn-sm btn-danger" href="direction_delete.php?id=<?= $d['id']; ?>" onclick="return doubleConfirm('Delete direction?');" data-i18n="directions.action_delete">Delete</a>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php include 'footer.php'; ?>
