<?php
include 'header.php';
$direction_id = $_GET['id'] ?? null;
if(!$direction_id){
    header('Location: directions.php');
    exit();
}
$direction_stmt = $pdo->prepare('SELECT * FROM research_directions WHERE id=?');
$direction_stmt->execute([$direction_id]);
$direction = $direction_stmt->fetch();
$current_stmt = $pdo->prepare('SELECT m.id, m.campus_id, m.name FROM direction_members dm JOIN members m ON dm.member_id=m.id WHERE dm.direction_id=? ORDER BY dm.sort_order');
$current_stmt->execute([$direction_id]);
$current_members = $current_stmt->fetchAll();
$members = $pdo->query('SELECT id, campus_id, name FROM members ORDER BY name')->fetchAll();
?>
<h2>研究方向成员 - <?= htmlspecialchars($direction['title']); ?></h2>
<table class="table table-bordered">
<tr><th></th><th>一卡通号</th><th>姓名</th><th>操作</th></tr>
<tbody id="memberList">
<?php foreach($current_members as $c): ?>
<tr data-id="<?= $c['id']; ?>">
  <td class="drag-handle">&#9776;</td>
  <td><?= htmlspecialchars($c['campus_id']); ?></td>
  <td><?= htmlspecialchars($c['name']); ?></td>
  <td><a class="btn btn-sm btn-danger" href="direction_member_remove.php?direction_id=<?= $direction_id; ?>&member_id=<?= $c['id']; ?>" onclick="return doubleConfirm('Remove member from direction?');">删除</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<h4>新增成员</h4>
<form method="post" action="direction_member_add.php">
  <input type="hidden" name="direction_id" value="<?= $direction_id; ?>">
  <div class="mb-3">
    <label class="form-label">成员</label>
    <select name="member_id" class="form-select" required>
      <option value="">选择成员</option>
<?php foreach($members as $m): ?>
      <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['name']); ?> (<?= $m['campus_id']; ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="submit" class="btn btn-primary">新增</button>
  <a href="directions.php" class="btn btn-secondary">返回</a>
</form>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  Sortable.create(document.getElementById('memberList'), {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function() {
      const order = Array.from(document.querySelectorAll('#memberList tr')).map((row, index) => row.dataset.id);
      fetch('direction_member_order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({direction_id: <?= $direction_id; ?>, order: order})
      });
    }
  });
});
</script>
<?php include 'footer.php'; ?>
