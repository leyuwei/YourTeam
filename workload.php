<?php
include 'auth_manager.php';
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';
$report = [];
$error = '';
if($start && $end){
    if(strtotime($end) <= strtotime($start)){
        $error = '报表截止时间必须晚于起始时间';
    } else {
        $members = $pdo->query("SELECT id, campus_id, name FROM members WHERE status != 'exited'")->fetchAll();
    foreach($members as $m){
        $total_task = 0;
        $task_hours = [];
        $stmt = $pdo->prepare('SELECT t.title, a.description, a.start_time, a.end_time FROM task_affairs a JOIN task_affair_members am ON a.id=am.affair_id JOIN tasks t ON a.task_id=t.id WHERE am.member_id=? AND a.start_time < ? AND a.end_time > ?');
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
        echo "<table border='1'>";
        echo "<tr><th>排名</th><th>一卡通号</th><th>姓名</th><th>具体任务</th><th>任务投入时长</th></tr>";
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
<h2>工作量统计报表</h2>
<?php if($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="get" class="row g-3 mb-3">
  <div class="col-auto">
    <label class="form-label">报表起始时间</label>
    <input type="date" name="start" class="form-control" value="<?= htmlspecialchars($start); ?>" required>
  </div>
  <div class="col-auto">
    <label class="form-label">报表截止时间</label>
    <input type="date" name="end" class="form-control" value="<?= htmlspecialchars($end); ?>" required>
  </div>
  <div class="col-auto align-self-end">
    <button type="submit" class="btn btn-primary">生成报表</button>
  </div>
  <?php if($report): ?>
  <div class="col-auto align-self-end">
    <a class="btn btn-success" href="workload.php?start=<?= urlencode($start); ?>&end=<?= urlencode($end); ?>&export=1">导出为EXCEL</a>
  </div>
  <?php endif; ?>
</form>
<script>
const rangeForm = document.querySelector('form');
rangeForm.addEventListener('submit', function(e){
  const startField = rangeForm.querySelector('input[name="start"]').value;
  const endField = rangeForm.querySelector('input[name="end"]').value;
  if(startField && endField && new Date(endField) <= new Date(startField)){
    alert('报表截止时间必须晚于起始时间');
    e.preventDefault();
  }
});
</script>
<?php if($report): ?>
<table class="table table-bordered">
<tr><th>排名</th><th>一卡通号</th><th>姓名</th><th>具体任务</th><th>任务投入时长</th></tr>
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
<?php include 'footer.php'; ?>
