<?php include 'header.php';
$status = $_GET['status'] ?? '';
if($status){
    $stmt = $pdo->prepare('SELECT p.*, GROUP_CONCAT(m.name ORDER BY l.sort_order SEPARATOR ", ") AS members FROM projects p LEFT JOIN project_member_log l ON p.id=l.project_id AND l.exit_time IS NULL LEFT JOIN members m ON l.member_id=m.id WHERE p.status=? GROUP BY p.id ORDER BY p.id DESC');
    $stmt->execute([$status]);
    $projects = $stmt->fetchAll();
} else {
    $projects = $pdo->query('SELECT p.*, GROUP_CONCAT(m.name ORDER BY l.sort_order SEPARATOR ", ") AS members FROM projects p LEFT JOIN project_member_log l ON p.id=l.project_id AND l.exit_time IS NULL LEFT JOIN members m ON l.member_id=m.id GROUP BY p.id ORDER BY p.id DESC')->fetchAll();
}
?>
<div class="d-flex justify-content-between mb-3">
  <h2 data-i18n="projects.title">Projects</h2>
  <div>
    <a class="btn btn-success" href="project_edit.php" data-i18n="projects.add">Add Project</a>
  </div>
</div>
<form class="row g-3 mb-3" method="get">
  <div class="col-auto">
    <select name="status" class="form-select">
      <option value="" data-i18n="projects.filter_all">All Status</option>
      <option value="todo" <?= $status=='todo'?'selected':''; ?> data-i18n="projects.filter.todo">Todo</option>
      <option value="ongoing" <?= $status=='ongoing'?'selected':''; ?> data-i18n="projects.filter.ongoing">Ongoing</option>
      <option value="paused" <?= $status=='paused'?'selected':''; ?> data-i18n="projects.filter.paused">Paused</option>
      <option value="finished" <?= $status=='finished'?'selected':''; ?> data-i18n="projects.filter.finished">Finished</option>
    </select>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-primary" data-i18n="projects.filter.button">Filter</button>
  </div>
</form>
<table class="table table-bordered">
<tr>
  <th data-i18n="projects.table_title">Title</th>
  <th data-i18n="projects.table_members">Members</th>
  <th data-i18n="projects.table_begin">Begin</th>
  <th data-i18n="projects.table_end">End</th>
  <th data-i18n="projects.table_status">Status</th>
  <th data-i18n="projects.table_actions">Actions</th>
</tr>
<?php foreach($projects as $p): ?>
<tr>
  <td><?= htmlspecialchars($p['title']); ?></td>
  <td><?= htmlspecialchars($p['members']); ?></td>
  <td><?= htmlspecialchars($p['begin_date']); ?></td>
  <td><?= htmlspecialchars($p['end_date']); ?></td>
  <td><?= htmlspecialchars($p['status']); ?></td>
  <td>
    <a class="btn btn-sm btn-primary" href="project_edit.php?id=<?= $p['id']; ?>" data-i18n="projects.action_edit">Edit</a>
    <a class="btn btn-sm btn-warning" href="project_members.php?id=<?= $p['id']; ?>" data-i18n="projects.action_members">Members</a>
    <a class="btn btn-sm btn-danger" href="project_delete.php?id=<?= $p['id']; ?>" onclick="return doubleConfirm('Delete project?');" data-i18n="projects.action_delete">Delete</a>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php include 'footer.php'; ?>
