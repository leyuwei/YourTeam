<?php
include 'auth.php';
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';
$report = [];
if($start && $end){
    $members = $pdo->query('SELECT id, campus_id, name FROM members')->fetchAll();
    foreach($members as $m){
        $total = 0;
        $project_hours = [];
        $stmt = $pdo->prepare('SELECT project_id, join_time, exit_time FROM project_member_log WHERE member_id=? AND join_time < ? AND (exit_time IS NULL OR exit_time > ?)');
        $stmt->execute([$m['id'],$end,$start]);
        foreach($stmt as $row){
            $join = max($row['join_time'],$start);
            $exit = $row['exit_time'] ? min($row['exit_time'],$end) : $end;
            if(strtotime($exit) > strtotime($join)){
                $seconds = strtotime($exit) - strtotime($join);
                $project_hours[$row['project_id']] = ($project_hours[$row['project_id']] ?? 0) + $seconds;
                $total += $seconds;
            }
        }
        $projects = [];
        if($project_hours){
            $ids = array_keys($project_hours);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $titles_stmt = $pdo->prepare("SELECT id, title FROM projects WHERE id IN ($placeholders)");
            $titles_stmt->execute($ids);
            $titles = $titles_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach($project_hours as $pid=>$sec){
                $projects[] = ['title'=>$titles[$pid] ?? ('Project '.$pid),'hours'=>round($sec/3600,2)];
            }
        }

        $task_hours = [];
        $stmt = $pdo->prepare('SELECT t.title, a.start_time, a.end_time FROM task_affairs a JOIN tasks t ON a.task_id=t.id WHERE a.member_id=? AND a.start_time < ? AND a.end_time > ?');
        $stmt->execute([$m['id'],$end,$start]);
        foreach($stmt as $row){
            $join = max($row['start_time'],$start);
            $exit = min($row['end_time'],$end);
            if(strtotime($exit) > strtotime($join)){
                $seconds = strtotime($exit) - strtotime($join);
                $task_hours[$row['title']] = ($task_hours[$row['title']] ?? 0) + $seconds;
                $total += $seconds;
            }
        }
        $tasks = [];
        foreach($task_hours as $title=>$sec){
            $tasks[] = ['title'=>$title,'hours'=>round($sec/3600,2)];
        }

        $report[] = [
            'campus_id'=>$m['campus_id'],
            'name'=>$m['name'],
            'projects'=>$projects,
            'tasks'=>$tasks,
            'total_hours'=>round($total/3600,2)
        ];
    }
    if(isset($_GET['export'])){
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="workload.csv"');
        $out = fopen('php://output','w');
        fputcsv($out,['CampusID','Name','Type','Item','Hours']);
        foreach($report as $r){
            foreach($r['projects'] as $p){
                fputcsv($out, [$r['campus_id'],$r['name'],'Project',$p['title'],$p['hours']]);
            }
            foreach($r['tasks'] as $t){
                fputcsv($out, [$r['campus_id'],$r['name'],'Urgent Task',$t['title'],$t['hours']]);
            }
            fputcsv($out, [$r['campus_id'],$r['name'],'Total','',$r['total_hours']]);
        }
        fclose($out);
        exit();
    }
}
include 'header.php';
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
<tr><th>Campus ID</th><th>Name</th><th>Projects</th><th>Urgent Tasks</th><th>Total Hours</th></tr>
<?php foreach($report as $r): ?>
<tr>
  <td><?= htmlspecialchars($r['campus_id']); ?></td>
  <td><?= htmlspecialchars($r['name']); ?></td>
  <td>
    <?php foreach($r['projects'] as $p): ?>
      <?= htmlspecialchars($p['title']); ?> (<?= htmlspecialchars($p['hours']); ?>h)<br>
    <?php endforeach; ?>
  </td>
  <td>
    <?php foreach($r['tasks'] as $t): ?>
      <?= htmlspecialchars($t['title']); ?> (<?= htmlspecialchars($t['hours']); ?>h)<br>
    <?php endforeach; ?>
  </td>
  <td><?= htmlspecialchars($r['total_hours']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php include 'footer.php'; ?>
