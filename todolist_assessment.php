<?php
include 'header.php';
$user_id = $_SESSION['role']==='manager' ? $_SESSION['manager_id'] : $_SESSION['member_id'];
$role = $_SESSION['role'];
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');
// initialize records and stats per category
$records = ['work'=>[], 'personal'=>[], 'longterm'=>[]];
$stats = ['work'=>['done'=>0,'total'=>0], 'personal'=>['done'=>0,'total'=>0], 'longterm'=>['done'=>0,'total'=>0]];
$cat_labels = ['work'=>'工作','personal'=>'私人','longterm'=>'长期'];
if(!empty($_GET['start']) && !empty($_GET['end'])){
    $expr = "DATE_ADD(week_start, INTERVAL CASE day WHEN 'mon' THEN 0 WHEN 'tue' THEN 1 WHEN 'wed' THEN 2 WHEN 'thu' THEN 3 WHEN 'fri' THEN 4 WHEN 'sat' THEN 5 WHEN 'sun' THEN 6 ELSE 0 END DAY)";
    $sql = "SELECT *, $expr AS item_date FROM todolist_items WHERE user_id=? AND user_role=? AND $expr BETWEEN ? AND ? ORDER BY item_date, sort_order";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id,$role,$start,$end]);
    foreach($stmt as $row){
        $cat = $row['category'];
        if(isset($records[$cat])){
            $records[$cat][] = $row;
            $stats[$cat]['total']++;
            if($row['is_done']) $stats[$cat]['done']++;
        }
    }
    foreach($records as &$rows){
        usort($rows, function($a,$b){return $b['is_done'] <=> $a['is_done'];});
    }
    unset($rows);
}
?>
<h2 class="text-center"><span data-i18n="todolist.assessment">待办统计</span></h2>
<form method="get" class="mb-3 d-flex flex-wrap align-items-center gap-2">
  <input type="date" name="start" value="<?= htmlspecialchars($start); ?>" class="form-control w-auto">
  <input type="date" name="end" value="<?= htmlspecialchars($end); ?>" class="form-control w-auto">
  <button type="submit" class="btn btn-primary" data-i18n="todolist.assessment.generate">统计</button>
</form>
<?php $total_all = $stats['work']['total'] + $stats['personal']['total'] + $stats['longterm']['total']; ?>
<?php if($total_all>0): ?>
  <?php foreach(['work','personal','longterm'] as $cat): ?>
    <h4><span data-i18n="todolist.category.<?= $cat ?>"><?= $cat_labels[$cat]; ?></span> <small>(<?= $stats[$cat]['done']; ?>/<?= $stats[$cat]['total']; ?>)</small></h4>
    <?php if($stats[$cat]['total']>0): ?>
    <ul class="list-group mb-3">
      <?php foreach($records[$cat] as $r): ?>
      <li class="list-group-item d-flex justify-content-between">
        <span><?= htmlspecialchars($r['content']); ?></span>
        <span><?= $r['is_done'] ? '✅' : '❌'; ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="text-muted" data-i18n="todolist.assessment.no_items">无待办事项</p>
    <?php endif; ?>
  <?php endforeach; ?>
<?php else: ?>
  <p class="text-muted" data-i18n="todolist.assessment.no_items">无待办事项</p>
<?php endif; ?>
<?php include 'footer.php'; ?>
