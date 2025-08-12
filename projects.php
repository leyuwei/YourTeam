<?php include 'header.php';
$status = $_GET['status'] ?? '';
if($status){
    $stmt = $pdo->prepare('SELECT p.*, GROUP_CONCAT(CONCAT(m.name, "(", COALESCE(m.degree_pursuing, ""), ",", COALESCE(m.year_of_join, ""), ")") ORDER BY l.sort_order SEPARATOR ", ") AS members FROM projects p LEFT JOIN project_member_log l ON p.id=l.project_id AND l.exit_time IS NULL LEFT JOIN members m ON l.member_id=m.id AND m.status != "exited" WHERE p.status=? GROUP BY p.id ORDER BY p.sort_order');
    $stmt->execute([$status]);
    $projects = $stmt->fetchAll();
} else {
    $projects = $pdo->query('SELECT p.*, GROUP_CONCAT(CONCAT(m.name, "(", COALESCE(m.degree_pursuing, ""), ",", COALESCE(m.year_of_join, ""), ")") ORDER BY l.sort_order SEPARATOR ", ") AS members FROM projects p LEFT JOIN project_member_log l ON p.id=l.project_id AND l.exit_time IS NULL LEFT JOIN members m ON l.member_id=m.id AND m.status != "exited" GROUP BY p.id ORDER BY p.sort_order')->fetchAll();
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
  <thead>
  <tr>
    <th></th>
    <th data-i18n="projects.table_title">Title</th>
    <th data-i18n="projects.table_members">Members</th>
    <th data-i18n="projects.table_begin">Begin</th>
    <th data-i18n="projects.table_end">End</th>
    <th data-i18n="projects.table_status">Status</th>
    <th data-i18n="projects.table_actions">Actions</th>
  </tr>
  </thead>
  <tbody id="projectList">
  <?php foreach($projects as $p): ?>
  <tr data-id="<?= $p['id']; ?>">
    <td class="drag-handle">&#9776;</td>
    <td><?= htmlspecialchars($p['title']); ?></td>
    <td><?= preg_replace('/\([^()]*\)/', '<span style="color:#cccccc;">$0</span>', htmlspecialchars($p['members'])); ?></td>
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
  </tbody>
</table>
<h3 class="mt-4">项目参与人员情况</h3>
<table class="table table-bordered">
  <tr><th>成员</th><th>参与项目</th></tr>
  <?php
  $memberProjects = $pdo->query("SELECT m.name, m.degree_pursuing, m.year_of_join, GROUP_CONCAT(p.title ORDER BY l.sort_order SEPARATOR ', ') AS proj FROM members m LEFT JOIN project_member_log l ON m.id=l.member_id AND l.exit_time IS NULL LEFT JOIN projects p ON l.project_id=p.id WHERE m.status != 'exited' GROUP BY m.id ORDER BY m.sort_order")->fetchAll();
  foreach($memberProjects as $mp): ?>
  <tr>
    <td><?= htmlspecialchars($mp["name"]); ?><span style="color:#cccccc;">(<?= htmlspecialchars($mp["degree_pursuing"]); ?>,<?= htmlspecialchars($mp["year_of_join"]); ?>)</span></td>
    <td><?= $mp['proj'] ? htmlspecialchars($mp['proj']) : '<span style="color:red"><em>暂无</em></span>'; ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  Sortable.create(document.getElementById('projectList'), {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function(){
      const order = Array.from(document.querySelectorAll('#projectList tr')).map((row, index) => ({id: row.dataset.id, position: index}));
      fetch('project_order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({order: order})
      });
    }
  });
});
</script>
<?php include 'footer.php'; ?>
