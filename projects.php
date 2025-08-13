<?php include 'header.php';
$status = $_GET['status'] ?? '';
$dirMapStmt = $pdo->query("SELECT dm.member_id, GROUP_CONCAT(rd.title SEPARATOR ', ') AS dirs FROM direction_members dm JOIN research_directions rd ON dm.direction_id=rd.id GROUP BY dm.member_id");
$memberDirections = [];
foreach($dirMapStmt as $row){
    $memberDirections[$row['member_id']] = $row['dirs'];
}
if($status){
    $stmt = $pdo->prepare('SELECT p.*, GROUP_CONCAT(CONCAT(m.id, "|", m.name, "|", COALESCE(m.degree_pursuing, ""), "|", COALESCE(m.year_of_join, "")) ORDER BY l.sort_order SEPARATOR ";") AS member_data FROM projects p LEFT JOIN project_member_log l ON p.id=l.project_id AND l.exit_time IS NULL LEFT JOIN members m ON l.member_id=m.id AND m.status != "exited" WHERE p.status=? GROUP BY p.id ORDER BY p.sort_order');
    $stmt->execute([$status]);
    $projects = $stmt->fetchAll();
} else {
    $projects = $pdo->query('SELECT p.*, GROUP_CONCAT(CONCAT(m.id, "|", m.name, "|", COALESCE(m.degree_pursuing, ""), "|", COALESCE(m.year_of_join, "")) ORDER BY l.sort_order SEPARATOR ";") AS member_data FROM projects p LEFT JOIN project_member_log l ON p.id=l.project_id AND l.exit_time IS NULL LEFT JOIN members m ON l.member_id=m.id AND m.status != "exited" GROUP BY p.id ORDER BY p.sort_order')->fetchAll();
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
<div class="form-check form-switch mb-3">
  <input class="form-check-input" type="checkbox" id="detailToggle" checked>
  <label class="form-check-label" for="detailToggle">显示成员详情</label>
</div>
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
  <?php
    $memberList = [];
    if ($p['member_data']) {
        foreach(explode(';', $p['member_data']) as $md){
            if(!$md) continue;
            list($mid,$mname,$mdegree,$myear) = explode('|',$md);
            $memberList[] = ['id'=>$mid,'name'=>$mname,'degree'=>$mdegree,'year'=>$myear];
        }
    }
  ?>
  <tr data-id="<?= $p['id']; ?>"<?= $p['bg_color'] ? ' style="background-color:'.htmlspecialchars($p['bg_color']).';"' : ''; ?>>
    <td class="drag-handle">&#9776;</td>
    <td><?= htmlspecialchars($p['title']); ?></td>
    <td>
      <?php if($memberList): ?>
        <?php foreach($memberList as $idx=>$m): ?>
          <span class="member-name" data-bs-toggle="tooltip" title="<?= htmlspecialchars($memberDirections[$m['id']] ?? '无研究方向'); ?>">
            <?= htmlspecialchars($m['name']); ?>
            <span class="member-detail text-muted">(<?= htmlspecialchars($m['degree']); ?>,<?= htmlspecialchars($m['year']); ?>)</span>
          </span><?= $idx < count($memberList)-1 ? ', ' : ''; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <em data-i18n="directions.none">None</em>
      <?php endif; ?>
    </td>
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
    <td><?= htmlspecialchars($mp["name"]); ?><span class="member-detail text-muted">(<?= htmlspecialchars($mp["degree_pursuing"]); ?>,<?= htmlspecialchars($mp["year_of_join"]); ?>)</span></td>
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

  document.getElementById('detailToggle').addEventListener('change', function(){
    document.querySelectorAll('.member-detail').forEach(span => {
      span.style.display = this.checked ? 'inline' : 'none';
    });
  });

  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });
});
</script>
<?php include 'footer.php'; ?>
