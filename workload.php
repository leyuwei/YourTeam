<?php
include 'header.php';
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';
$report = [];
if($start && $end){
    $members = $pdo->query('SELECT id, campus_id, name FROM members')->fetchAll();
    foreach($members as $m){
        $total = 0;
        $stmt = $pdo->prepare('SELECT join_time, exit_time FROM project_member_log WHERE member_id=? AND join_time < ? AND (exit_time IS NULL OR exit_time > ?)');
        $stmt->execute([$m['id'],$end,$start]);
        foreach($stmt as $row){
            $join = max($row['join_time'],$start);
            $exit = $row['exit_time'] ? min($row['exit_time'],$end) : $end;
            if(strtotime($exit) > strtotime($join)){
                $total += strtotime($exit) - strtotime($join);
            }
        }
        $stmt = $pdo->prepare('SELECT start_time, end_time FROM task_affairs WHERE member_id=? AND start_time < ? AND end_time > ?');
        $stmt->execute([$m['id'],$end,$start]);
        foreach($stmt as $row){
            $join = max($row['start_time'],$start);
            $exit = min($row['end_time'],$end);
            if(strtotime($exit) > strtotime($join)){
                $total += strtotime($exit) - strtotime($join);
            }
        }
        $hours = round($total/3600,2);
        $report[] = ['campus_id'=>$m['campus_id'],'name'=>$m['name'],'hours'=>$hours];
    }
    if(isset($_GET['export'])){
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="workload.csv"');
        $out = fopen('php://output','w');
        fputcsv($out,['CampusID','Name','Hours']);
        foreach($report as $r){
            fputcsv($out, $r);
        }
        fclose($out);
        exit();
    }
}
?>
<h2>Member Workload Report</h2>
<form method="get" class="row g-3 mb-3">
  <div class="col-auto">
    <label class="form-label">Start</label>
    <input type="datetime-local" name="start" class="form-control" value="<?= htmlspecialchars($start); ?>" required>
  </div>
  <div class="col-auto">
    <label class="form-label">End</label>
    <input type="datetime-local" name="end" class="form-control" value="<?= htmlspecialchars($end); ?>" required>
  </div>
  <div class="col-auto align-self-end">
    <button type="submit" class="btn btn-primary">Generate</button>
  </div>
  <?php if($report): ?>
  <div class="col-auto align-self-end">
    <a class="btn btn-success" href="workload.php?start=<?= urlencode($start); ?>&end=<?= urlencode($end); ?>&export=1">Export CSV</a>
  </div>
  <?php endif; ?>
</form>
<?php if($report): ?>
<table class="table table-bordered">
<tr><th>Campus ID</th><th>Name</th><th>Hours</th></tr>
<?php foreach($report as $r): ?>
<tr>
  <td><?= htmlspecialchars($r['campus_id']); ?></td>
  <td><?= htmlspecialchars($r['name']); ?></td>
  <td><?= htmlspecialchars($r['hours']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php include 'footer.php'; ?>
