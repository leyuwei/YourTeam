<?php
include 'header.php';
$user_id = $_SESSION['role']==='manager' ? $_SESSION['manager_id'] : $_SESSION['member_id'];
$role = $_SESSION['role'];
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');
$records = [];
if(!empty($_GET['start']) && !empty($_GET['end'])){
    $expr = "DATE_ADD(week_start, INTERVAL CASE day WHEN 'mon' THEN 0 WHEN 'tue' THEN 1 WHEN 'wed' THEN 2 WHEN 'thu' THEN 3 WHEN 'fri' THEN 4 WHEN 'sat' THEN 5 WHEN 'sun' THEN 6 ELSE 0 END DAY)";
    $sql = "SELECT *, $expr AS item_date FROM todolist_items WHERE user_id=? AND user_role=? AND $expr BETWEEN ? AND ? ORDER BY item_date, category, sort_order";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id,$role,$start,$end]);
    foreach($stmt as $row){
        $records[$row['item_date']][] = $row;
    }
}
$cat_labels = ['work'=>'工作','personal'=>'私人','longterm'=>'长期'];
?>
<h2 class="text-center">待办统计</h2>
<form method="get" class="mb-3 d-flex flex-wrap align-items-center gap-2">
  <input type="date" name="start" value="<?= htmlspecialchars($start); ?>" class="form-control w-auto">
  <input type="date" name="end" value="<?= htmlspecialchars($end); ?>" class="form-control w-auto">
  <button type="submit" class="btn btn-primary">统计</button>
</form>
<?php if($records): ?>
  <?php foreach($records as $date=>$rows): ?>
    <h4><?= htmlspecialchars($date); ?></h4>
    <ul class="list-group mb-3">
    <?php foreach($rows as $r): ?>
      <li class="list-group-item d-flex justify-content-between">
        <span>[<?= $cat_labels[$r['category']] ?? $r['category']; ?>] <?= htmlspecialchars($r['content']); ?></span>
        <span><?= $r['is_done'] ? '✅' : '❌'; ?></span>
      </li>
    <?php endforeach; ?>
    </ul>
  <?php endforeach; ?>
<?php else: ?>
  <p class="text-muted">无待办事项</p>
<?php endif; ?>
<?php include 'footer.php'; ?>
