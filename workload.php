<?php
include 'auth_manager.php';
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';
$lang = $_GET['lang'] ?? 'zh';
$report = [];
$error = '';
if($start && $end){
    if(strtotime($end) <= strtotime($start)){
        $error = 'workload.error.range';
    } else {
        $members = $pdo->query("SELECT id, campus_id, name FROM members WHERE status != 'exited'")->fetchAll();
    foreach($members as $m){
        $total_task = 0;
        $task_hours = [];
        $stmt = $pdo->prepare('SELECT t.title, a.description, a.start_time, a.end_time FROM task_affairs a JOIN task_affair_members am ON a.id=am.affair_id JOIN tasks t ON a.task_id=t.id WHERE am.member_id=? AND a.start_time < ? AND a.end_time > ? AND a.status="confirmed"');
        $stmt->execute([$m['id'],$end,$start]);
        foreach($stmt as $row){
            $join = max($row['start_time'],$start);
            $exit = min($row['end_time'],$end);
            if(strtotime($exit) > strtotime($join)){
                $seconds = strtotime($exit) - strtotime($join);
                $key = $row['title'].' - '.$row['description'];
                $task_hours[$key] = ($task_hours[$key] ?? 0) + $seconds;
                $total_task += $seconds;
            }
        }
        $tasks = [];
        foreach($task_hours as $key=>$sec){
            $tasks[] = ['key'=>$key,'hours'=>round($sec/3600,2)];
        }

        $report[] = [
            'campus_id'=>$m['campus_id'],
            'name'=>$m['name'],
            'tasks'=>$tasks,
            'task_total'=>round($total_task/3600,2),
            'total_hours'=>round($total_task/3600,2)
        ];
    }
    usort($report, function($a,$b){ return $b['total_hours'] <=> $a['total_hours']; });
    foreach($report as $i=>$r){
        $report[$i]['rank'] = $i + 1;
    }
    if(isset($_GET['export'])){
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="workload.xls"');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        $labels = [
            'en'=>['rank'=>'Rank','campus_id'=>'Campus ID','name'=>'Name','tasks'=>'Tasks','hours'=>'Task Hours'],
            'zh'=>['rank'=>'排名','campus_id'=>'一卡通号','name'=>'姓名','tasks'=>'具体任务','hours'=>'任务投入时长']
        ];
        echo "<table border='1'>";
        echo "<tr><th>".$labels[$lang]['rank']."</th><th>".$labels[$lang]['campus_id']."</th><th>".$labels[$lang]['name']."</th><th>".$labels[$lang]['tasks']."</th><th>".$labels[$lang]['hours']."</th></tr>";
        foreach($report as $r){
            echo "<tr>";
            echo "<td>".htmlspecialchars($r['rank'])."</td>";
            echo "<td>".htmlspecialchars($r['campus_id'])."</td>";
            echo "<td>".htmlspecialchars($r['name'])."</td>";
            echo "<td>";
            foreach($r['tasks'] as $t){
                echo htmlspecialchars($t['key'])." (".htmlspecialchars($t['hours'])."h)<br>";
            }
            echo "</td>";
            echo "<td>".htmlspecialchars($r['task_total'])."</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit();
    }
    }
}
include 'header.php';
?>
<h2 data-i18n="workload.title">Workload Report</h2>
<?php if($error): ?><div class="alert alert-danger" data-i18n="<?= $error; ?>"></div><?php endif; ?>
<form method="get" class="row g-3 mb-3">
  <div class="col-auto">
    <label class="form-label" data-i18n="workload.label.start">Start Date</label>
    <input type="date" name="start" class="form-control" value="<?= htmlspecialchars($start); ?>" required>
  </div>
  <div class="col-auto">
    <label class="form-label" data-i18n="workload.label.end">End Date</label>
    <input type="date" name="end" class="form-control" value="<?= htmlspecialchars($end); ?>" required>
  </div>
  <div class="col-auto align-self-end">
    <button type="submit" class="btn btn-primary" data-i18n="workload.generate">Generate</button>
  </div>
  <?php if($report): ?>
  <div class="col-auto align-self-end">
    <a class="btn btn-success" id="exportBtn" href="workload.php?start=<?= urlencode($start); ?>&end=<?= urlencode($end); ?>&export=1&lang=<?= $lang; ?>" data-i18n="workload.export">Export to EXCEL</a>
  </div>
  <?php endif; ?>
</form>
<script>
const rangeForm = document.querySelector('form');
rangeForm.addEventListener('submit', function(e){
  const startField = rangeForm.querySelector('input[name="start"]').value;
  const endField = rangeForm.querySelector('input[name="end"]').value;
  if(startField && endField && new Date(endField) <= new Date(startField)){
    alert(translations[document.documentElement.lang]['workload.error.range']);
    e.preventDefault();
  }
});
</script>
<?php if($report): ?>
<table class="table table-bordered">
<tr><th data-i18n="workload.table.rank">Rank</th><th data-i18n="workload.table.campus_id">Campus ID</th><th data-i18n="workload.table.name">Name</th><th data-i18n="workload.table.task_detail">Task Detail</th><th data-i18n="workload.table.task_hours">Task Hours</th></tr>
<?php foreach($report as $r): ?>
<tr>
  <td><?= htmlspecialchars($r['rank']); ?></td>
  <td><?= htmlspecialchars($r['campus_id']); ?></td>
  <td><?= htmlspecialchars($r['name']); ?></td>
  <td>
    <?php foreach($r['tasks'] as $t): ?>
      <?= htmlspecialchars($t['key']); ?> (<?= htmlspecialchars($t['hours']); ?>h)<br>
    <?php endforeach; ?>
  </td>
  <td><?= htmlspecialchars($r['task_total']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<script>
document.getElementById('exportBtn')?.addEventListener('click',function(){
  const lang=document.documentElement.lang||'zh';
  this.href=`workload.php?start=<?= urlencode($start); ?>&end=<?= urlencode($end); ?>&export=1&lang=${lang}`;
});
</script>
<?php include 'footer.php'; ?>
