<?php include 'header.php';
// Fetch research directions along with their members' details
$stmt = $pdo->query("SELECT d.*, GROUP_CONCAT(CONCAT(m.name, '(', COALESCE(m.degree_pursuing, ''), ',', COALESCE(m.year_of_join, ''), ')') ORDER BY dm.sort_order SEPARATOR ', ') AS member_names
                     FROM research_directions d
                     LEFT JOIN direction_members dm ON d.id = dm.direction_id
                     LEFT JOIN members m ON dm.member_id = m.id AND m.status != 'exited'
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
<div class="form-check form-switch mb-3">
  <input class="form-check-input" type="checkbox" id="detailToggle" checked>
  <label class="form-check-label" for="detailToggle">显示成员详情</label>
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
  <tr data-id="<?= $d['id']; ?>"<?= $d['bg_color'] ? ' style="background-color:'.htmlspecialchars($d['bg_color']).';"' : ''; ?>>
    <td class="drag-handle">&#9776;</td>
    <td><?= htmlspecialchars($d['title']); ?></td>
    <td>
      <?php
      if ($d['member_names']) {
          $escaped = htmlspecialchars($d['member_names']);
          echo preg_replace('/\(([^()]*)\)/', '<span class="member-detail text-muted">($1)</span>', $escaped);
      } else {
          echo '<em data-i18n="directions.none">None</em>';
      }
      ?>
    </td>
    <td>
      <a class="btn btn-sm btn-primary" href="direction_edit.php?id=<?= $d['id']; ?>" data-i18n="directions.action_edit">Edit</a>
      <a class="btn btn-sm btn-warning" href="direction_members.php?id=<?= $d['id']; ?>" data-i18n="directions.action_members">Members</a>
      <a class="btn btn-sm btn-danger" href="direction_delete.php?id=<?= $d['id']; ?>" onclick="return doubleConfirm('Delete direction?');" data-i18n="directions.action_delete">Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<h3 class="mt-4">研究方向指派情况</h3>
<table class="table table-bordered">
  <tr><th>成员</th><th>研究方向</th></tr>
  <?php
  $memberDirs = $pdo->query("SELECT m.name, m.degree_pursuing, m.year_of_join, GROUP_CONCAT(d.title ORDER BY dm.sort_order SEPARATOR ', ') AS dirs FROM members m LEFT JOIN direction_members dm ON m.id=dm.member_id LEFT JOIN research_directions d ON dm.direction_id=d.id WHERE m.status != 'exited' GROUP BY m.id ORDER BY m.sort_order")->fetchAll();
  foreach($memberDirs as $md): ?>
  <tr>
    <td><?= htmlspecialchars($md["name"]); ?><span class="member-detail text-muted">(<?= htmlspecialchars($md["degree_pursuing"]); ?>,<?= htmlspecialchars($md["year_of_join"]); ?>)</span></td>
    <td><?= $md['dirs'] ? htmlspecialchars($md['dirs']) : '<span style="color:red"><em>暂无</em></span>'; ?></td>
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

  document.getElementById('detailToggle').addEventListener('change', function(){
    document.querySelectorAll('.member-detail').forEach(span => {
      span.style.display = this.checked ? 'inline' : 'none';
    });
  });
});
</script>
<?php include 'footer.php'; ?>
