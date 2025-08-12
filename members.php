<?php
include 'header.php';

// Determine sorting column and direction from query parameters
$columns = [
    'campus_id' => '一卡通号',
    'name' => '姓名',
    'email' => '正式邮箱',
    'identity_number' => '身份证号',
    'year_of_join' => '入学年份',
    'current_degree' => '已获学位',
    'degree_pursuing' => '当前学历',
    'phone' => '手机号',
    'wechat' => '微信号',
    'department' => '所处学院/单位',
    'workplace' => '工作地点',
    'homeplace' => '家庭住址'
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
  <h2>团队成员</h2>
  <div>
    <a class="btn btn-success" href="member_edit.php">新增成员</a>
    <a class="btn btn-secondary" href="members_import.php">从表格导入</a>
    <a class="btn btn-secondary" href="members_export.php">导出至表格</a>
    <a class="btn btn-warning" href="member_self_update.php">请求信息更新</a>
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
    <th>操作</th>
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
      <a class="btn btn-sm btn-primary" href="member_edit.php?id=<?= $m['id']; ?>">编辑</a>
      <a class="btn btn-sm btn-danger" href="member_delete.php?id=<?= $m['id']; ?>" onclick="return doubleConfirm('确认要移除该成员吗? 此操作需万分谨慎！');">移除</a>
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
