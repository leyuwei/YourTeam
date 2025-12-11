<?php
include 'auth_manager.php';
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';
$lang = $_GET['lang'] ?? 'zh';
$selectedCategories = $_GET['categories'] ?? [];
if(!is_array($selectedCategories)){
    $selectedCategories = [$selectedCategories];
}
$selectedCategories = array_values(array_unique(array_filter(array_map('intval', $selectedCategories))));
$sortInput = $_GET['sort'] ?? 'total_desc';
$sortKeys = array_values(array_filter(explode(',', $sortInput)));
$report = [];
$categoryReport = [];
$summaryCards = ['total_days'=>0,'member_count'=>0,'category_variety'=>0];
$error = '';

$hasTaskCategory = false;
$categoryColumnCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tasks' AND column_name = 'category'");
$categoryColumnCheck->execute();
$hasTaskCategory = $categoryColumnCheck->fetchColumn() > 0;
$categoryLabelSql = $hasTaskCategory ? "COALESCE(NULLIF(t.category, ''), t.title)" : "t.title";
$categoryLabelAlias = $hasTaskCategory ? 'task_category' : 'task_title_as_category';

$taskCatalogStmt = $pdo->query("SELECT id, title, {$categoryLabelSql} AS {$categoryLabelAlias} FROM tasks ORDER BY {$categoryLabelAlias} ASC, title ASC");
$taskCatalog = $taskCatalogStmt->fetchAll();

if($start && $end){
    if(strtotime($end) <= strtotime($start)){
        $error = 'workload.error.range';
    } else {
        $members = $pdo->query("SELECT id, campus_id, name FROM members WHERE status != 'exited'")->fetchAll();
        foreach($members as $m){
            $total_task = 0;
            $task_hours = [];
            $task_categories = [];
            $params = [$m['id'],$end,$start];
            $categoryWhere = '';
            if($selectedCategories){
                $placeholders = implode(',', array_fill(0, count($selectedCategories), '?'));
                $categoryWhere = " AND t.id IN ({$placeholders})";
                $params = array_merge($params, $selectedCategories);
            }
            $stmt = $pdo->prepare("SELECT t.id AS task_id, t.title, {$categoryLabelSql} AS category_label, a.description, a.start_time, a.end_time FROM task_affairs a JOIN task_affair_members am ON a.id=am.affair_id JOIN tasks t ON a.task_id=t.id WHERE am.member_id=? AND a.start_time < ? AND a.end_time > ? AND a.status=\"confirmed\"{$categoryWhere}");
            $stmt->execute($params);
            foreach($stmt as $row){
                $join = max($row['start_time'],$start);
                $exit = min($row['end_time'],$end);
                if(strtotime($exit) > strtotime($join)){
                    $seconds = strtotime($exit) - strtotime($join);
                    $categoryKey = $row['category_label'] ?: $row['title'];
                    $key = $row['title'].' - '.$row['description'];
                    $task_hours[$key] = ($task_hours[$key] ?? 0) + $seconds;
                    $task_categories[$categoryKey] = ($task_categories[$categoryKey] ?? 0) + $seconds;
                    $total_task += $seconds;
                    if(!isset($categoryReport[$categoryKey])){
                        $categoryReport[$categoryKey] = [];
                    }
                    $categoryReport[$categoryKey][$m['name']] = ($categoryReport[$categoryKey][$m['name']] ?? 0) + $seconds;
                }
            }
            $tasks = [];
            foreach($task_hours as $key=>$sec){
                $tasks[] = ['key'=>$key,'days'=>round($sec/86400,2)];
            }
            $categoryTotals = [];
            foreach($task_categories as $cat=>$sec){
                $categoryTotals[$cat] = round($sec/86400,2);
            }

            $memberTotalDays = round($total_task/86400,2);
            $summaryCards['total_days'] += $memberTotalDays;

            $report[] = [
                'campus_id'=>$m['campus_id'],
                'name'=>$m['name'],
                'tasks'=>$tasks,
                'categories'=>$categoryTotals,
                'category_count'=>count($categoryTotals),
                'task_total'=>round($total_task/86400,2),
                'total_hours'=>round($total_task/86400,2)
            ];
        }
        $summaryCards['member_count'] = count($report);
        $summaryCards['category_variety'] = count(array_keys($categoryReport));

        $sorter = function($a,$b) use ($sortKeys){
            $map = [
                'total_desc'=>function($x,$y){ return $y['total_hours'] <=> $x['total_hours']; },
                'total_asc'=>function($x,$y){ return $x['total_hours'] <=> $y['total_hours']; },
                'categories_desc'=>function($x,$y){ return $y['category_count'] <=> $x['category_count']; },
                'categories_asc'=>function($x,$y){ return $x['category_count'] <=> $y['category_count']; },
                'name_asc'=>function($x,$y){ return strcmp($x['name'],$y['name']); },
                'name_desc'=>function($x,$y){ return strcmp($y['name'],$x['name']); },
            ];
            foreach($sortKeys as $key){
                if(!isset($map[$key])) continue;
                $cmp = $map[$key]($a,$b);
                if($cmp !== 0) return $cmp;
            }
            return strcmp($a['name'],$b['name']);
        };
        usort($report, $sorter);
        foreach($report as $i=>$r){
            $report[$i]['rank'] = $i + 1;
        }
        if(isset($_GET['export_txt'])){
            header('Content-Type: text/plain; charset=UTF-8');
            header('Content-Disposition: attachment; filename="workload.txt"');
            echo "\xEF\xBB\xBF";
            $labels = [
                'en'=>[
                    'title'=>'Workload Report',
                    'range'=>'Date Range',
                    'category'=>'Category',
                    'member'=>'Member',
                    'days'=>'days',
                    'empty'=>'No workload records in this range.',
                ],
                'zh'=>[
                    'title'=>'工作量报表',
                    'range'=>'统计区间',
                    'category'=>'任务类别',
                    'member'=>'成员',
                    'days'=>'天',
                    'empty'=>'该时间范围内暂无工作量记录。',
                ]
            ];
            $lines = [];
            $lines[] = $labels[$lang]['title'];
            $lines[] = $labels[$lang]['range'] . ": {$start} ~ {$end}";
            if(!$categoryReport){
                $lines[] = $labels[$lang]['empty'];
            }
            ksort($categoryReport);
            foreach($categoryReport as $cat=>$entries){
                $lines[] = "";
                $lines[] = "# " . $labels[$lang]['category'] . ': ' . $cat;
                arsort($entries);
                foreach($entries as $memberName=>$seconds){
                    $lines[] = "- " . $labels[$lang]['member'] . " " . $memberName . " (" . round($seconds/86400,2) . " " . $labels[$lang]['days'] . ")";
                }
            }
            echo implode("\n", $lines);
            exit();
        }
        if(isset($_GET['export'])){
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="workload.xls"');
            echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
            $labels = [
                'en'=>['rank'=>'Rank','campus_id'=>'Campus ID','name'=>'Name','tasks'=>'Tasks','hours'=>'Task Days','categories'=>'Task Categories'],
                'zh'=>['rank'=>'排名','campus_id'=>'一卡通号','name'=>'姓名','tasks'=>'具体任务','hours'=>'任务投入天数','categories'=>'参与任务类别数']
            ];
            echo "<table border='1'>";
            echo "<tr><th>".$labels[$lang]['rank']."</th><th>".$labels[$lang]['campus_id']."</th><th>".$labels[$lang]['name']."</th><th>".$labels[$lang]['tasks']."</th><th>".$labels[$lang]['categories']."</th><th>".$labels[$lang]['hours']."</th></tr>";
            foreach($report as $r){
                echo "<tr>";
                echo "<td>".htmlspecialchars($r['rank'])."</td>";
                echo "<td>".htmlspecialchars($r['campus_id'])."</td>";
                echo "<td>".htmlspecialchars($r['name'])."</td>";
                echo "<td>";
                foreach($r['tasks'] as $t){
                    echo htmlspecialchars($t['key'])." (".htmlspecialchars($t['days'])."d)<br>";
                }
                echo "</td>";
                echo "<td>".htmlspecialchars($r['category_count'])."</td>";
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
<form method="get" class="row g-3 mb-3 align-items-end" id="workloadForm">
  <div class="col-md-3">
    <label class="form-label" data-i18n="workload.label.start">Start Date</label>
    <input type="date" name="start" class="form-control" value="<?= htmlspecialchars($start); ?>" required>
  </div>
  <div class="col-md-3">
    <label class="form-label" data-i18n="workload.label.end">End Date</label>
    <input type="date" name="end" class="form-control" value="<?= htmlspecialchars($end); ?>" required>
  </div>
  <div class="col-md-3">
    <label class="form-label" data-i18n="workload.label.category_filter">Task Categories</label>
    <select name="categories[]" class="form-select" multiple size="4">
      <?php foreach($taskCatalog as $task): ?>
        <?php $label = $task[$categoryLabelAlias]; ?>
        <option value="<?= $task['id']; ?>" <?= in_array((int)$task['id'],$selectedCategories)?'selected':''; ?>><?= htmlspecialchars($label ?: $task['title']); ?></option>
      <?php endforeach; ?>
    </select>
    <div class="form-text" data-i18n="workload.label.category_hint">Hold Ctrl/Cmd to multi-select.</div>
  </div>
  <div class="col-md-3">
    <label class="form-label" data-i18n="workload.sort.title">Sort Priority</label>
    <div class="d-flex gap-2">
      <?php $primarySort = $sortKeys[0] ?? 'total_desc'; $secondarySort = $sortKeys[1] ?? 'categories_desc'; ?>
      <select class="form-select" id="primarySort">
        <option value="total_desc" <?= $primarySort==='total_desc'?'selected':''; ?> data-i18n="workload.sort.total_desc">Total Days ↓</option>
        <option value="total_asc" <?= $primarySort==='total_asc'?'selected':''; ?> data-i18n="workload.sort.total_asc">Total Days ↑</option>
        <option value="categories_desc" <?= $primarySort==='categories_desc'?'selected':''; ?> data-i18n="workload.sort.category_desc">Category Count ↓</option>
        <option value="categories_asc" <?= $primarySort==='categories_asc'?'selected':''; ?> data-i18n="workload.sort.category_asc">Category Count ↑</option>
        <option value="name_asc" <?= $primarySort==='name_asc'?'selected':''; ?> data-i18n="workload.sort.name_asc">Name A→Z</option>
        <option value="name_desc" <?= $primarySort==='name_desc'?'selected':''; ?> data-i18n="workload.sort.name_desc">Name Z→A</option>
      </select>
      <select class="form-select" id="secondarySort">
        <option value="" data-i18n="workload.sort.none">None</option>
        <option value="total_desc" <?= $secondarySort==='total_desc'?'selected':''; ?> data-i18n="workload.sort.total_desc">Total Days ↓</option>
        <option value="total_asc" <?= $secondarySort==='total_asc'?'selected':''; ?> data-i18n="workload.sort.total_asc">Total Days ↑</option>
        <option value="categories_desc" <?= $secondarySort==='categories_desc'?'selected':''; ?> data-i18n="workload.sort.category_desc">Category Count ↓</option>
        <option value="categories_asc" <?= $secondarySort==='categories_asc'?'selected':''; ?> data-i18n="workload.sort.category_asc">Category Count ↑</option>
        <option value="name_asc" <?= $secondarySort==='name_asc'?'selected':''; ?> data-i18n="workload.sort.name_asc">Name A→Z</option>
        <option value="name_desc" <?= $secondarySort==='name_desc'?'selected':''; ?> data-i18n="workload.sort.name_desc">Name Z→A</option>
      </select>
    </div>
  </div>
  <input type="hidden" name="sort" id="sortInput" value="<?= htmlspecialchars($sortInput); ?>">
  <input type="hidden" name="lang" value="<?= htmlspecialchars($lang); ?>">
  <div class="col-md-12 d-flex flex-wrap gap-2">
    <button type="submit" class="btn btn-primary" data-i18n="workload.generate">Generate</button>
    <button type="button" class="btn btn-outline-secondary" id="thisMonthBtn" data-i18n="workload.quick.this_month">This Month</button>
    <button type="button" class="btn btn-outline-secondary" id="lastMonthBtn" data-i18n="workload.quick.last_month">Last Month</button>
    <div class="d-flex align-items-center gap-2">
      <input type="month" class="form-control" id="monthPicker">
      <button type="button" class="btn btn-outline-info" id="applyMonthBtn" data-i18n="workload.quick.pick_month">Apply</button>
    </div>
    <?php if($report): ?>
    <a class="btn btn-success" id="exportBtn" href="#" data-i18n="workload.export">Export to EXCEL</a>
    <a class="btn btn-dark" id="exportTxtBtn" href="#" data-i18n="workload.export_txt">Export to TXT</a>
    <?php endif; ?>
  </div>
</form>
<?php if($report): ?>
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <p class="text-muted mb-1" data-i18n="workload.card.total">Total Workload (days)</p>
        <h4 class="fw-bold text-primary mb-0"><?= htmlspecialchars(number_format($summaryCards['total_days'], 2)); ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <p class="text-muted mb-1" data-i18n="workload.card.members">Members Count</p>
        <h4 class="fw-bold text-success mb-0"><?= htmlspecialchars($summaryCards['member_count']); ?></h4>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <p class="text-muted mb-1" data-i18n="workload.card.categories">Distinct Categories</p>
        <h4 class="fw-bold text-warning mb-0"><?= htmlspecialchars($summaryCards['category_variety']); ?></h4>
      </div>
    </div>
  </div>
</div>
<table class="table table-bordered align-middle">
<tr>
  <th data-i18n="workload.table.rank">Rank</th>
  <th data-i18n="workload.table.campus_id">Campus ID</th>
  <th data-i18n="workload.table.name">Name</th>
  <th data-i18n="workload.table.category_count">Category Count</th>
  <th data-i18n="workload.table.category_breakdown">Category Breakdown</th>
  <th data-i18n="workload.table.task_detail">Task Detail</th>
  <th data-i18n="workload.table.task_days">Task Days</th>
</tr>
<?php foreach($report as $r): ?>
<tr>
  <td><?= htmlspecialchars($r['rank']); ?></td>
  <td><?= htmlspecialchars($r['campus_id']); ?></td>
  <td><?= htmlspecialchars($r['name']); ?></td>
  <td><span class="badge bg-secondary"><?= htmlspecialchars($r['category_count']); ?></span></td>
  <td>
    <?php if($r['categories']): foreach($r['categories'] as $cat=>$days): ?>
      <div class="mb-1"><span class="badge bg-info text-dark"><?= htmlspecialchars($cat); ?></span> <small class="text-muted"><?= htmlspecialchars($days); ?>d</small></div>
    <?php endforeach; else: ?>
      <span class="text-muted" data-i18n="workload.table.none">None</span>
    <?php endif; ?>
  </td>
  <td>
    <?php foreach($r['tasks'] as $t): ?>
      <div class="mb-1">
        <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($t['days']); ?>d</span>
        <span><?= htmlspecialchars($t['key']); ?></span>
      </div>
    <?php endforeach; ?>
  </td>
  <td><?= htmlspecialchars($r['task_total']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<script>
const rangeForm = document.getElementById('workloadForm');
rangeForm.addEventListener('submit', function(e){
  const startField = rangeForm.querySelector('input[name="start"]').value;
  const endField = rangeForm.querySelector('input[name="end"]').value;
  if(startField && endField && new Date(endField) <= new Date(startField)){
    alert(translations[document.documentElement.lang]['workload.error.range']);
    e.preventDefault();
  }
});

function setMonthRange(offset = 0){
  const today = new Date();
  const year = today.getFullYear();
  const month = today.getMonth() + offset;
  const first = new Date(year, month, 1);
  const last = new Date(year, month + 1, 0);
  rangeForm.querySelector('input[name="start"]').value = first.toISOString().slice(0,10);
  rangeForm.querySelector('input[name="end"]').value = last.toISOString().slice(0,10);
}
document.getElementById('thisMonthBtn')?.addEventListener('click', ()=>setMonthRange(0));
document.getElementById('lastMonthBtn')?.addEventListener('click', ()=>setMonthRange(-1));
document.getElementById('applyMonthBtn')?.addEventListener('click', ()=>{
  const picker = document.getElementById('monthPicker');
  if(!picker.value) return;
  const [year, month] = picker.value.split('-').map(v=>parseInt(v,10));
  const first = new Date(year, month - 1, 1);
  const last = new Date(year, month, 0);
  rangeForm.querySelector('input[name="start"]').value = first.toISOString().slice(0,10);
  rangeForm.querySelector('input[name="end"]').value = last.toISOString().slice(0,10);
});

function syncSortInput(){
  const sortInput = document.getElementById('sortInput');
  const primary = document.getElementById('primarySort').value;
  const secondary = document.getElementById('secondarySort').value;
  const parts = [primary, secondary].filter((v,i,arr)=>v && arr.indexOf(v)===i);
  sortInput.value = parts.join(',') || 'total_desc';
}
document.getElementById('primarySort').addEventListener('change', syncSortInput);
document.getElementById('secondarySort').addEventListener('change', syncSortInput);
syncSortInput();

function buildExportUrl(base){
  const params = new URLSearchParams(new FormData(rangeForm));
  const lang=document.documentElement.lang||'zh';
  params.set('lang', lang);
  return base + '?' + params.toString();
}
document.getElementById('exportBtn')?.addEventListener('click',function(e){
  e.preventDefault();
  this.href = buildExportUrl('workload.php') + '&export=1';
  window.location.href = this.href;
});
document.getElementById('exportTxtBtn')?.addEventListener('click',function(e){
  e.preventDefault();
  this.href = buildExportUrl('workload.php') + '&export_txt=1';
  window.location.href = this.href;
});
</script>
<?php include 'footer.php'; ?>
