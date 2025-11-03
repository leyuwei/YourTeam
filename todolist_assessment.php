<?php
include 'header.php';
$user_id = $_SESSION['role']==='manager' ? $_SESSION['manager_id'] : $_SESSION['member_id'];
$role = $_SESSION['role'];
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');
$requested_export = isset($_GET['export']) && $_GET['export'] === 'txt';
// initialize records and stats per category
$records = ['work'=>[], 'personal'=>[], 'longterm'=>[]];
$stats = ['work'=>['done'=>0,'total'=>0], 'personal'=>['done'=>0,'total'=>0], 'longterm'=>['done'=>0,'total'=>0]];
$cat_labels = ['work'=>'工作','personal'=>'私人','longterm'=>'长期'];
$weekday_fallback = ['mon'=>'周一','tue'=>'周二','wed'=>'周三','thu'=>'周四','fri'=>'周五','sat'=>'周六','sun'=>'周日'];
if(!empty($_GET['start']) && !empty($_GET['end'])){
    $expr = "DATE_ADD(week_start, INTERVAL CASE day WHEN 'mon' THEN 0 WHEN 'tue' THEN 1 WHEN 'wed' THEN 2 WHEN 'thu' THEN 3 WHEN 'fri' THEN 4 WHEN 'sat' THEN 5 WHEN 'sun' THEN 6 ELSE 0 END DAY)";
    $sql = "SELECT *, $expr AS item_date FROM todolist_items WHERE user_id=? AND user_role=? AND $expr BETWEEN ? AND ? ORDER BY item_date DESC, sort_order DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id,$role,$start,$end]);
    foreach($stmt as $row){
        $cat = $row['category'];
        if(isset($records[$cat])){
            $itemDate = $row['item_date'] ?? $row['week_start'];
            try {
                $dateObj = new DateTime($itemDate ?? $row['week_start']);
                $formattedDate = $dateObj->format('Y-m-d');
                $weekdayKey = $row['day'] ?? strtolower($dateObj->format('D'));
            } catch (Exception $e) {
                $formattedDate = $row['item_date'] ?? $row['week_start'];
                $weekdayKey = $row['day'] ?? 'mon';
            }
            $row['item_date'] = $itemDate;
            $row['item_date_formatted'] = $formattedDate;
            $row['weekday_key'] = in_array($weekdayKey, ['mon','tue','wed','thu','fri','sat','sun'], true) ? $weekdayKey : 'mon';
            $row['weekday_label'] = $weekday_fallback[$row['weekday_key']] ?? $row['weekday_key'];
            $records[$cat][] = $row;
            $stats[$cat]['total']++;
            if($row['is_done']) $stats[$cat]['done']++;
        }
    }
    foreach($records as &$rows){
        usort($rows, function($a,$b){
            $dateComparison = strcmp($b['item_date_formatted'], $a['item_date_formatted']);
            if($dateComparison !== 0){
                return $dateComparison;
            }
            return ((int)($b['sort_order'] ?? 0)) <=> ((int)($a['sort_order'] ?? 0));
        });
    }
    unset($rows);
}
$total_all = $stats['work']['total'] + $stats['personal']['total'] + $stats['longterm']['total'];

if($requested_export){
    $lang = $_GET['lang'] ?? 'zh';
    $lang = $lang === 'en' ? 'en' : 'zh';
    $categoryTitles = [
        'en' => ['work' => 'Work', 'personal' => 'Personal', 'longterm' => 'Long Term'],
        'zh' => ['work' => '工作', 'personal' => '私人', 'longterm' => '长期'],
    ];
    $weekdayLabels = [
        'en' => ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'],
        'zh' => $weekday_fallback,
    ];
    $statusLabels = [
        'en' => ['done' => 'Completed', 'todo' => 'Pending'],
        'zh' => ['done' => '已完成', 'todo' => '未完成'],
    ];
    $titleLine = $lang === 'en'
        ? sprintf('Todo Assessment (%s to %s)', $start, $end)
        : sprintf('待办事项统计（%s - %s）', $start, $end);
    $lines = [$titleLine];
    foreach(['work','personal','longterm'] as $cat){
        $lines[] = '';
        $catTitle = $categoryTitles[$lang][$cat] ?? $cat;
        $doneCount = $stats[$cat]['done'];
        $totalCount = $stats[$cat]['total'];
        $lines[] = sprintf('[%s] %d/%d', $catTitle, $doneCount, $totalCount);
        if(!empty($records[$cat])){
            foreach($records[$cat] as $item){
                $badgeDate = $item['item_date_formatted'] ?? ($item['item_date'] ?? '');
                $weekdayKey = $item['weekday_key'] ?? 'mon';
                $weekdayText = $weekdayLabels[$lang][$weekdayKey] ?? $weekdayKey;
                $statusKey = $item['is_done'] ? 'done' : 'todo';
                $statusText = $statusLabels[$lang][$statusKey] ?? $statusKey;
                $statusSymbol = $item['is_done'] ? '✅' : '❌';
                $content = $item['content'] ?? '';
                $lines[] = sprintf('- [%s · %s] %s %s', $badgeDate, $weekdayText, $content, $statusSymbol . ' ' . $statusText);
            }
        } else {
            $lines[] = $lang === 'en' ? '  (No todo items in this category)' : '  （该分类暂无待办事项）';
        }
    }
    $output = implode(PHP_EOL, $lines);
    header('Content-Type: text/plain; charset=UTF-8');
    $filename = sprintf('todolist_%s_%s.txt', $start, $end);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    echo $output;
    exit;
}
?>
<h2 class="text-center"><span data-i18n="todolist.assessment">待办统计</span></h2>
<form method="get" class="mb-3 d-flex flex-wrap align-items-center gap-2">
  <input type="date" name="start" value="<?= htmlspecialchars($start); ?>" class="form-control w-auto">
  <input type="date" name="end" value="<?= htmlspecialchars($end); ?>" class="form-control w-auto">
  <button type="submit" class="btn btn-primary" data-i18n="todolist.assessment.generate">统计</button>
  <button type="button" class="btn btn-outline-secondary" id="exportAssessment" data-i18n="todolist.assessment.export_txt">导出TXT</button>
</form>
<?php if($total_all>0): ?>
  <?php foreach(['work','personal','longterm'] as $cat): ?>
    <h4><span data-i18n="todolist.category.<?= $cat ?>"><?= $cat_labels[$cat]; ?></span> <small>(<?= $stats[$cat]['done']; ?>/<?= $stats[$cat]['total']; ?>)</small></h4>
    <?php if($stats[$cat]['total']>0): ?>
    <ul class="list-group mb-3">
      <?php foreach($records[$cat] as $r): ?>
      <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
          <span class="badge rounded-pill text-bg-info px-3 py-2">
            <span class="badge-date fw-semibold"><?= htmlspecialchars($r['item_date_formatted'] ?? ($r['item_date'] ?? '')); ?></span>
            <span class="mx-1">·</span>
            <span class="badge-day" data-i18n="todolist.days.<?= htmlspecialchars($r['weekday_key']); ?>"><?= htmlspecialchars($r['weekday_label']); ?></span>
          </span>
          <span class="todo-content"><?= htmlspecialchars($r['content']); ?></span>
        </div>
        <span class="status-indicator" aria-label="<?= $r['is_done'] ? '已完成' : '未完成'; ?>" data-i18n-title="todolist.assessment.status.<?= $r['is_done'] ? 'done' : 'todo'; ?>" title="<?= $r['is_done'] ? '已完成' : '未完成'; ?>"><?= $r['is_done'] ? '✅' : '❌'; ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="text-muted" data-i18n="todolist.assessment.no_items">无待办事项</p>
    <?php endif; ?>
  <?php endforeach; ?>
  <div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span data-i18n="todolist.assessment.prompts.title">AI 提示词备选</span>
      <span class="badge rounded-pill text-bg-light text-secondary" data-i18n="todolist.assessment.prompts.helper_badge">AI 助手</span>
    </div>
    <div class="card-body">
      <p class="text-muted" data-i18n="todolist.assessment.prompts.description" data-i18n-params='<?= json_encode(['start'=>$start,'end'=>$end], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'>请将以下提示词复制到你的 AI 工具中，帮助其总结在所选日期范围内三大类事项的重点。</p>
      <div class="list-group">
        <?php $promptParams = json_encode(['start'=>$start,'end'=>$end], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>
        <div class="list-group-item">
          <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-start">
            <div class="prompt-text small flex-grow-1" style="white-space: pre-line;" id="prompt-text-1" data-i18n="todolist.assessment.prompts.item1" data-i18n-params='<?= $promptParams; ?>'>请扮演专业周报整理助手，基于我在所选日期范围内（<?= htmlspecialchars($start); ?> 至 <?= htmlspecialchars($end); ?>）记录的待办事项，将“工作”“私人”“长期”三类里的高价值事件逐条总结，注意不同描述下可能是同一件事，请进行关联归纳。</div>
            <button type="button" class="btn btn-outline-primary btn-sm copy-prompt align-self-lg-start" data-target="prompt-text-1" data-i18n="todolist.assessment.prompts.copy">复制提示词</button>
          </div>
        </div>
        <div class="list-group-item">
          <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-start">
            <div class="prompt-text small flex-grow-1" style="white-space: pre-line;" id="prompt-text-2" data-i18n="todolist.assessment.prompts.item2" data-i18n-params='<?= $promptParams; ?>'>请帮我对<?= htmlspecialchars($start); ?> 至 <?= htmlspecialchars($end); ?>期间的待办事项做复盘，分“工作”“私人”“长期”总结关键成果，识别重复描述的同一事务并合并成统一条目，清楚列出每条结论。</div>
            <button type="button" class="btn btn-outline-primary btn-sm copy-prompt align-self-lg-start" data-target="prompt-text-2" data-i18n="todolist.assessment.prompts.copy">复制提示词</button>
          </div>
        </div>
        <div class="list-group-item">
          <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-start">
            <div class="prompt-text small flex-grow-1" style="white-space: pre-line;" id="prompt-text-3" data-i18n="todolist.assessment.prompts.item3" data-i18n-params='<?= $promptParams; ?>'>基于我在<?= htmlspecialchars($start); ?> 到 <?= htmlspecialchars($end); ?>期间的待办记录，请总结三大分类下最有代表性的行动，要善于识别措辞不同但本质相同的事项并合并，最终按条目输出每类的重点事项清单。</div>
            <button type="button" class="btn btn-outline-primary btn-sm copy-prompt align-self-lg-start" data-target="prompt-text-3" data-i18n="todolist.assessment.prompts.copy">复制提示词</button>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>
  <p class="text-muted" data-i18n="todolist.assessment.no_items">无待办事项</p>
<?php endif; ?>
<?php include 'footer.php'; ?>
