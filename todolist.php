<?php
include 'header.php';
$user_id = $_SESSION['role']==='manager' ? $_SESSION['manager_id'] : $_SESSION['member_id'];
$role = $_SESSION['role'];
$week_param = $_GET['week'] ?? date('o-\WW');
if(preg_match('/^(\d{4})-W(\d{2})$/',$week_param,$m)){
    $dt = new DateTime();
    $dt->setISODate($m[1], $m[2]);
    $week_start = $dt->format('Y-m-d');
} else {
    $dt = new DateTime();
    $dt->setISODate(date('o'), date('W'));
    $week_start = $dt->format('Y-m-d');
    $week_param = $dt->format('o-\WW');
}
$stmt = $pdo->prepare('SELECT * FROM todolist_items WHERE user_id=? AND user_role=? AND week_start=? ORDER BY sort_order');
$stmt->execute([$user_id,$role,$week_start]);
$items = [];
foreach($stmt as $row){
    $items[$row['category']][$row['day']][] = $row;
}
$commonStmt = $pdo->prepare('SELECT id, content FROM todolist_common_items WHERE user_id=? AND user_role=? ORDER BY sort_order, id');
$commonStmt->execute([$user_id,$role]);
$common_items = $commonStmt->fetchAll(PDO::FETCH_ASSOC);
$stats = ['work'=>['done'=>0,'total'=>0],
          'personal'=>['done'=>0,'total'=>0],
          'longterm'=>['done'=>0,'total'=>0]];
foreach($items as $cat=>$daysArr){
    foreach($daysArr as $dayItems){
        foreach($dayItems as $it){
            $stats[$cat]['total']++;
            if($it['is_done']) $stats[$cat]['done']++;
        }
    }
}
$week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
$next_week_param = date('o-\\WW', strtotime($week_start . ' +7 days'));
$prev_week_param = date('o-\\WW', strtotime($week_start . ' -7 days'));
$current_week_param = date('o-\\WW');
$last_week_param = date('o-\\WW', strtotime('-1 week'));
$next_week_hint_param = date('o-\\WW', strtotime('+1 week'));
$week_hint = '';
if($week_param === $current_week_param){
    $week_hint = '<div class="week-hint text-center mb-2 fs-4"><span class="badge bg-primary" data-i18n="todolist.week.current">本周</span></div>';
} elseif($week_param === $last_week_param){
    $week_hint = '<div class="week-hint text-center mb-2 fs-4"><span class="badge bg-secondary" data-i18n="todolist.week.last">上周</span></div>';
} elseif($week_param === $next_week_hint_param){
    $week_hint = '<div class="week-hint text-center mb-2 fs-4"><span class="badge bg-secondary" data-i18n="todolist.week.next">下周</span></div>';
}
$days = ['mon'=>'周一','tue'=>'周二','wed'=>'周三','thu'=>'周四','fri'=>'周五','sat'=>'周六','sun'=>'周日'];
$is_current_week = ($week_param === $current_week_param);
$today_key = strtolower(date('D'));
?>
<style>
.todolist li{flex-wrap:nowrap;}
.todolist li .item-content{flex:1 1 auto;min-width:0;margin-right:0.5rem;resize:none;overflow:hidden;white-space:pre-wrap;}
.todolist li .item-content.is-multiline{line-height:1.4;}
.todo-input-wrapper.is-multiline .todo-highlight-layer{align-items:flex-start;}
.todo-input-wrapper.is-multiline .todo-highlight-content{white-space:pre-wrap;word-break:break-word;}
.todolist li .drag-handle{cursor:grab;}
.todolist li .drag-handle:active{cursor:grabbing;}
.todolist li .copy-item{margin-left:auto;}
.todolist li .drag-handle,
.todolist li .copy-item,
.todolist li .next-week-item{white-space:nowrap;}
.todolist li .icon-btn{width:1.75rem;height:1.75rem;padding:0;display:inline-flex;align-items:center;justify-content:center;}
.todolist li .drag-handle.icon-btn{border:none;background:transparent;color:inherit;}
.todolist li .drag-handle.icon-btn:focus{box-shadow:0 0 0 0.15rem rgba(13,110,253,0.25);}
.todolist li .icon-btn svg{width:1rem;height:1rem;}
.today-heading{background:var(--app-highlight-bg);color:var(--app-highlight-text);padding:2px 4px;border-radius:4px;}
.today-heading .btn{color:inherit;border-color:var(--app-highlight-border);}
.today-heading .btn:hover,.today-heading .btn:focus{background-color:var(--app-highlight-button-hover);color:var(--app-text-color);}
.todolist.today{border-left:4px solid var(--app-highlight-border);padding-left:4px;background:var(--app-highlight-surface);}
.save-status{position:fixed;bottom:1.5rem;left:50%;transform:translateX(-50%);display:none;align-items:center;gap:0.5rem;padding:0.75rem 1.25rem;border-radius:999px;background:var(--app-surface-bg);border:1px solid var(--app-surface-border);box-shadow:0 0.75rem 1.5rem rgba(0,0,0,0.08);backdrop-filter:blur(12px);font-size:0.95rem;z-index:1080;color:#198754;font-weight:500;}
.save-status .status-indicator{width:0.65rem;height:0.65rem;border-radius:50%;background:currentColor;box-shadow:0 0 0 0 currentColor;flex:0 0 auto;}
.save-status[data-state='pending']{color:#0d6efd;border-color:rgba(13,110,253,0.3);}
.save-status[data-state='pending'] .status-indicator{animation:status-pulse 1.4s ease-in-out infinite;}
.save-status[data-state='success']{color:#198754;border-color:rgba(25,135,84,0.28);}
.save-status[data-state='error']{color:#b02a37;border-color:rgba(220,53,69,0.28);}
.save-status[data-state='error'] .status-indicator{animation:none;}
.save-status .status-text{white-space:nowrap;}
.common-suggestion-bar{position:fixed;left:0;top:0;z-index:1090;display:none;padding:0.65rem 0.75rem;border-radius:0.85rem;background:var(--app-surface-bg,#fff);border:1px solid rgba(13,110,253,0.24);box-shadow:0 1rem 2.5rem rgba(13,110,253,0.18);max-width:90vw;min-width:14rem;overflow-x:hidden;}
.common-suggestion-inner{display:flex;flex-direction:column;gap:0.35rem;}
.common-suggestion-header{font-size:0.75rem;letter-spacing:0.04em;font-weight:600;color:#6c757d;text-transform:uppercase;display:flex;align-items:center;gap:0.35rem;}
.common-suggestion-header::before{content:'';width:0.65rem;height:0.65rem;border-radius:50%;background:#0d6efd;opacity:0.45;}
.common-suggestion-list{display:flex;flex-wrap:wrap;gap:0.5rem;align-items:flex-start;padding-bottom:0.1rem;width:100%;}
.common-suggestion-pill{flex:1 1 calc(50% - 0.5rem);min-width:8.5rem;max-width:100%;border-radius:999px;border:1px solid rgba(13,110,253,0.38);background:rgba(13,110,253,0.08);color:#0d6efd;padding:0.35rem 0.75rem;font-size:0.85rem;line-height:1.25;white-space:normal;word-break:break-word;text-align:left;transition:background-color 0.2s ease,color 0.2s ease,border-color 0.2s ease;}
.common-suggestion-pill:hover,.common-suggestion-pill:focus{background:rgba(13,110,253,0.2);border-color:rgba(13,110,253,0.55);color:#0a58ca;}
.todo-common-highlight{background:linear-gradient(90deg,rgba(13,110,253,0.12),rgba(13,110,253,0.03));border-left:3px solid rgba(13,110,253,0.4);}
.todo-common-highlight .item-content{border-color:rgba(13,110,253,0.45);box-shadow:0 0 0 0.1rem rgba(13,110,253,0.12);}
.todo-common-highlight .item-content:focus{box-shadow:0 0 0 0.2rem rgba(13,110,253,0.18);}
.item-content.todo-common-match{border-color:rgba(13,110,253,0.55);box-shadow:0 0 0 0.15rem rgba(13,110,253,0.18);transition:box-shadow 0.2s ease,border-color 0.2s ease;}
.item-content.todo-common-match:focus{box-shadow:0 0 0 0.2rem rgba(13,110,253,0.24);}
.todo-common-highlight .item-content.todo-common-match{border-color:rgba(13,110,253,0.6);}
.todo-input-wrapper{position:relative;display:flex;align-items:stretch;flex:1 1 auto;border-radius:0.375rem;overflow:hidden;}
.todo-input-wrapper .item-content{flex:1 1 auto;width:100%;background-color:transparent!important;position:relative;z-index:2;}
.todo-highlight-layer{position:absolute;inset:0;pointer-events:none;display:flex;align-items:center;padding:0.375rem 0.75rem;font:inherit;line-height:inherit;white-space:pre;overflow:hidden;border-radius:inherit;background:var(--todo-input-bg,var(--bs-body-bg,#fff));color:transparent;z-index:1;}
.todo-highlight-content{display:inline-block;min-width:100%;white-space:pre;font:inherit;line-height:inherit;transform-origin:left top;}
.todo-highlight-layer mark{background:rgba(13,110,253,0.2);border-radius:0.25rem;box-shadow:0 0 0 1px rgba(13,110,253,0.12);padding:0 0.08em;color:transparent;}
.todo-common-highlight .todo-input-wrapper{--todo-input-bg:rgba(13,110,253,0.08);}
.common-items-manager .common-item-row{display:flex;align-items:center;gap:0.5rem;}
.common-items-manager .common-item-index{width:1.5rem;text-align:center;font-size:0.8rem;color:#6c757d;flex-shrink:0;}
.common-items-manager .common-item-input{flex:1 1 auto;}
.common-items-manager .btn-group{flex-shrink:0;}
.common-items-manager .common-item-input.is-invalid{border-color:#dc3545;box-shadow:0 0 0 0.15rem rgba(220,53,69,0.25);}
.common-items-manager-empty{display:none;}
.common-items-manager-empty[data-visible="true"]{display:block;}
.undo-delete-banner{position:fixed;left:50%;bottom:4.5rem;transform:translateX(-50%);display:none;align-items:center;gap:0.75rem;padding:0.85rem 1rem;border-radius:0.75rem;box-shadow:0 1rem 2.5rem rgba(0,0,0,0.12);z-index:1080;}
.undo-delete-banner .countdown{font-variant-numeric:tabular-nums;}
@keyframes status-pulse{0%{box-shadow:0 0 0 0 rgba(13,110,253,0.45);}70%{box-shadow:0 0 0 10px rgba(13,110,253,0);}100%{box-shadow:0 0 0 0 rgba(13,110,253,0);}}
@media (max-width:575.98px){.save-status{left:1rem;right:1rem;transform:none;justify-content:center;padding:0.65rem 1rem;border-radius:0.85rem;}.save-status .status-text{white-space:normal;text-align:center;}}
@media (max-width:575.98px){.common-suggestion-bar{left:0.75rem!important;right:0.75rem!important;width:auto!important;}}
@media print {
  @page { size: A4; margin: 10mm; }
  body { font-size: 12pt; }
  .navbar, form, .add-item, .delete-item, .btn { display: none !important; }
  .row, .col-md-6 { display: block !important; width: 100% !important; }
  .todolist { list-style: none; padding-left: 0; }
  .todolist li { margin-left: 5mm; display: flex; align-items: flex-start; }
  .todolist li .item-done { transform: scale(1.2); margin-right: 5mm; }
  .todolist li .item-content { border: none; box-shadow: none; padding: 0; font-size: 12pt; }
}
@media (max-width: 767.98px) {
  .todolist li .copy-item,
  .todolist li .next-week-item,
  .todolist li .tomorrow-item,
  .todolist li .delete-item,
  .add-item { display: none !important; }
  .todolist li .item-content { border: none; box-shadow: none; padding-left: 0; }
}
</style>
<h2 class="text-center"><span data-i18n="todolist.title">待办事项</span> @ <?= date('Y.m.d', strtotime($week_start)) ?> - <?= date('Y.m.d', strtotime($week_end)) ?></small></h2>
<?= $week_hint; ?>
<form method="get" class="mb-3 d-flex flex-wrap align-items-center gap-2">
  <input type="week" name="week" class="form-control form-control-lg w-auto" value="<?= htmlspecialchars($week_param); ?>">
  <a class="btn btn-outline-secondary" href="todolist.php?week=<?= urlencode($prev_week_param); ?>" data-i18n="todolist.prev_week">看上周</a>
  <a class="btn btn-outline-secondary" href="todolist.php?week=<?= urlencode($next_week_param); ?>" data-i18n="todolist.next_week">看下周</a>
  <a class="btn btn-success" href="todolist_export.php?week=<?= urlencode($week_param); ?>" data-i18n="todolist.export">导出</a>
  <a class="btn btn-info" href="todolist_assessment.php" data-i18n="todolist.assessment">待办统计</a>
  <button type="button" class="btn btn-secondary" id="copyNextWeek" data-i18n="todolist.copy_next">鸽下周</button>
  <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#commonItemsModal" data-i18n="todolist.common.manage">常用事项</button>
  <button type="button" class="btn btn-outline-primary" onclick="printTodoList()" data-i18n="todolist.print">打印</button>
</form>
<div class="row">
  <div class="col-md-6">
    <h3 data-category="work"><b data-i18n="todolist.category.work">工作</b> <small class="stats">(<?= $stats['work']['done']; ?>/<?= $stats['work']['total']; ?>)</small></h3>
    <?php foreach($days as $k=>$label): ?>
    <?php $is_today = $is_current_week && $k === $today_key; ?>
    <h5 class="<?= $is_today ? 'today-heading' : '' ?>"><span data-i18n="todolist.days.<?= $k ?>"><?= $label; ?></span> <button type="button" class="btn btn-sm btn-outline-success add-item" data-category="work" data-day="<?= $k; ?>">+</button></h5>
    <ul class="list-group mb-3 todolist<?= $is_today ? ' today' : '' ?>" data-category="work" data-day="<?= $k; ?>">
      <?php if(!empty($items['work'][$k])): foreach($items['work'][$k] as $it): ?>
      <li class="list-group-item d-flex align-items-center flex-nowrap" data-id="<?= $it['id']; ?>">
        <button type="button" class="btn btn-sm btn-outline-secondary me-2 drag-handle icon-btn" title="拖动排序" data-i18n-title="todolist.drag_handle" aria-label="拖动排序">
          <span class="visually-hidden" data-i18n="todolist.drag_handle">拖动排序</span>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
            <path d="M3 5h10M3 8h10M3 11h10" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"></path>
          </svg>
        </button>
        <input type="checkbox" class="form-check-input me-2 item-done" <?= $it['is_done']?'checked':''; ?>>
        <textarea class="form-control item-content flex-grow-1 me-2" rows="1" data-multiline="0"><?= htmlspecialchars($it['content']); ?></textarea>
        <button class="btn btn-sm btn-outline-secondary ms-auto copy-item icon-btn" title="复制" data-i18n-title="todolist.copy_item" aria-label="复制">
          <span class="visually-hidden" data-i18n="todolist.copy_item">复制</span>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
            <rect x="5.2" y="4.2" width="7.6" height="9.6" rx="1.3" ry="1.3" fill="none" stroke="currentColor" stroke-width="1.1"></rect>
            <rect x="3.2" y="2.2" width="7.6" height="9.6" rx="1.3" ry="1.3" fill="none" stroke="currentColor" stroke-width="1.1"></rect>
            <path d="M6.8 2.9h2.4" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
          </svg>
        </button>
        <button class="btn btn-sm btn-secondary ms-2 next-week-item icon-btn" title="鸽下周" data-i18n-title="todolist.copy_next" aria-label="鸽下周">
          <span class="visually-hidden" data-i18n="todolist.copy_next">鸽下周</span>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
            <rect x="2.6" y="3.6" width="10.8" height="8.8" rx="1.2" ry="1.2" fill="none" stroke="currentColor" stroke-width="1.1"></rect>
            <path d="M5.3 2.5v2.2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            <path d="M10.7 2.5v2.2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            <path d="M4 6.8h8" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            <path d="M6.2 9.7h3.2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            <path d="M8.6 8.3l1.9 1.4-1.9 1.4" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </button>
        <button class="btn btn-sm btn-outline-primary ms-2 tomorrow-item text-nowrap icon-btn" title="鸽明天" data-i18n-title="todolist.cut_tomorrow" aria-label="鸽明天">
          <span class="visually-hidden" data-i18n="todolist.cut_tomorrow">鸽明天</span>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
            <circle cx="8" cy="8" r="5.6" fill="none" stroke="currentColor" stroke-width="1.1"></circle>
            <path d="M6.5 8h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            <path d="M8.5 6.4 10.2 8 8.5 9.6" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </button>
        <button class="btn btn-sm btn-danger ms-2 delete-item">&times;</button>
      </li>
      <?php endforeach; endif; ?>
    </ul>
    <?php endforeach; ?>
  </div>
  <div class="col-md-6">
    <h3 data-category="personal"><b data-i18n="todolist.category.personal">私人</b> <small class="stats">(<?= $stats['personal']['done']; ?>/<?= $stats['personal']['total']; ?>)</small></h3>
    <?php foreach($days as $k=>$label): ?>
    <?php $is_today = $is_current_week && $k === $today_key; ?>
    <h5 class="<?= $is_today ? 'today-heading' : '' ?>"><span data-i18n="todolist.days.<?= $k ?>"><?= $label; ?></span> <button type="button" class="btn btn-sm btn-outline-success add-item" data-category="personal" data-day="<?= $k; ?>">+</button></h5>
    <ul class="list-group mb-3 todolist<?= $is_today ? ' today' : '' ?>" data-category="personal" data-day="<?= $k; ?>">
      <?php if(!empty($items['personal'][$k])): foreach($items['personal'][$k] as $it): ?>
      <li class="list-group-item d-flex align-items-center flex-nowrap" data-id="<?= $it['id']; ?>">
        <button type="button" class="btn btn-sm btn-outline-secondary me-2 drag-handle icon-btn" title="拖动排序" data-i18n-title="todolist.drag_handle" aria-label="拖动排序">
          <span class="visually-hidden" data-i18n="todolist.drag_handle">拖动排序</span>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
            <path d="M3 5h10M3 8h10M3 11h10" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"></path>
          </svg>
        </button>
        <input type="checkbox" class="form-check-input me-2 item-done" <?= $it['is_done']?'checked':''; ?>>
        <textarea class="form-control item-content flex-grow-1 me-2" rows="1" data-multiline="0"><?= htmlspecialchars($it['content']); ?></textarea>
        <button class="btn btn-sm btn-outline-secondary ms-auto copy-item icon-btn" title="复制" data-i18n-title="todolist.copy_item" aria-label="复制">
          <span class="visually-hidden" data-i18n="todolist.copy_item">复制</span>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
            <rect x="5.2" y="4.2" width="7.6" height="9.6" rx="1.3" ry="1.3" fill="none" stroke="currentColor" stroke-width="1.1"></rect>
            <rect x="3.2" y="2.2" width="7.6" height="9.6" rx="1.3" ry="1.3" fill="none" stroke="currentColor" stroke-width="1.1"></rect>
            <path d="M6.8 2.9h2.4" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
          </svg>
        </button>
        <button class="btn btn-sm btn-secondary ms-2 next-week-item icon-btn" title="鸽下周" data-i18n-title="todolist.copy_next" aria-label="鸽下周">
          <span class="visually-hidden" data-i18n="todolist.copy_next">鸽下周</span>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
            <rect x="2.6" y="3.6" width="10.8" height="8.8" rx="1.2" ry="1.2" fill="none" stroke="currentColor" stroke-width="1.1"></rect>
            <path d="M5.3 2.5v2.2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            <path d="M10.7 2.5v2.2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            <path d="M4 6.8h8" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            <path d="M6.2 9.7h3.2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            <path d="M8.6 8.3l1.9 1.4-1.9 1.4" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </button>
        <button class="btn btn-sm btn-outline-primary ms-2 tomorrow-item text-nowrap icon-btn" title="鸽明天" data-i18n-title="todolist.cut_tomorrow" aria-label="鸽明天">
          <span class="visually-hidden" data-i18n="todolist.cut_tomorrow">鸽明天</span>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
            <circle cx="8" cy="8" r="5.6" fill="none" stroke="currentColor" stroke-width="1.1"></circle>
            <path d="M6.5 8h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            <path d="M8.5 6.4 10.2 8 8.5 9.6" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </button>
        <button class="btn btn-sm btn-danger ms-2 delete-item">&times;</button>
      </li>
      <?php endforeach; endif; ?>
    </ul>
    <?php endforeach; ?>
    <h3 data-category="longterm"><b data-i18n="todolist.category.longterm">长期</b> <small class="stats">(<?= $stats['longterm']['done']; ?>/<?= $stats['longterm']['total']; ?>)</small> <button type="button" class="btn btn-sm btn-outline-success add-item" data-category="longterm" data-day="">+</button></h3>
    <ul class="list-group mb-3 todolist" data-category="longterm" data-day="">
      <?php if(!empty($items['longterm'][''])): foreach($items['longterm'][''] as $it): ?>
      <li class="list-group-item d-flex align-items-center flex-nowrap" data-id="<?= $it['id']; ?>">
        <button type="button" class="btn btn-sm btn-outline-secondary me-2 drag-handle icon-btn" title="拖动排序" data-i18n-title="todolist.drag_handle" aria-label="拖动排序">
          <span class="visually-hidden" data-i18n="todolist.drag_handle">拖动排序</span>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
            <path d="M3 5h10M3 8h10M3 11h10" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"></path>
          </svg>
        </button>
        <input type="checkbox" class="form-check-input me-2 item-done" <?= $it['is_done']?'checked':''; ?>>
        <textarea class="form-control item-content flex-grow-1 me-2" rows="1" data-multiline="0"><?= htmlspecialchars($it['content']); ?></textarea>
        <button class="btn btn-sm btn-outline-secondary ms-auto copy-item icon-btn" title="复制" data-i18n-title="todolist.copy_item" aria-label="复制">
          <span class="visually-hidden" data-i18n="todolist.copy_item">复制</span>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
            <rect x="5.2" y="4.2" width="7.6" height="9.6" rx="1.3" ry="1.3" fill="none" stroke="currentColor" stroke-width="1.1"></rect>
            <rect x="3.2" y="2.2" width="7.6" height="9.6" rx="1.3" ry="1.3" fill="none" stroke="currentColor" stroke-width="1.1"></rect>
            <path d="M6.8 2.9h2.4" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
          </svg>
        </button>
        <button class="btn btn-sm btn-secondary ms-2 next-week-item icon-btn" title="鸽下周" data-i18n-title="todolist.copy_next" aria-label="鸽下周">
          <span class="visually-hidden" data-i18n="todolist.copy_next">鸽下周</span>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
            <rect x="2.6" y="3.6" width="10.8" height="8.8" rx="1.2" ry="1.2" fill="none" stroke="currentColor" stroke-width="1.1"></rect>
            <path d="M5.3 2.5v2.2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            <path d="M10.7 2.5v2.2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            <path d="M4 6.8h8" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            <path d="M6.2 9.7h3.2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            <path d="M8.6 8.3l1.9 1.4-1.9 1.4" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </button>
        <button class="btn btn-sm btn-danger ms-2 delete-item">&times;</button>
      </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>
</div>
<div class="modal fade" id="commonItemsModal" tabindex="-1" aria-labelledby="commonItemsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="commonItemsModalLabel" data-i18n="todolist.common.title">常用事项库</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭" data-i18n-attr="aria-label:todolist.common.close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3" data-i18n="todolist.common.description">维护常用事项，在填写待办时可快速插入。</p>
        <div class="common-items-manager">
          <ul class="list-group mb-3" id="commonItemsList"></ul>
          <p class="text-muted common-items-manager-empty" id="commonItemsEmpty" data-visible="false" data-i18n="todolist.common.empty">暂无常用事项，请新增。</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" id="addCommonItem" data-i18n="todolist.common.add">新增常用事项</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="todolist.common.close">关闭</button>
      </div>
    </div>
  </div>
</div>
<div id="saveStatus" class="save-status" role="status" aria-live="polite" aria-atomic="true" aria-hidden="true" data-state="pending" style="display:none;">
  <span class="status-indicator" aria-hidden="true"></span>
  <span class="status-text">保存中…</span>
</div>
<div id="undoDeleteBanner" class="undo-delete-banner alert alert-warning align-items-center" role="status" aria-live="polite" aria-hidden="true">
  <span class="undo-text" data-i18n="todolist.delete.undo_hint">待办事项已删除</span>
  <button type="button" class="btn btn-sm btn-outline-secondary undo-button" data-i18n="todolist.delete.undo_button">撤销</button>
  <span class="countdown text-muted small" aria-hidden="true"></span>
</div>
<script>
window.commonTodoItems = <?= json_encode($common_items, JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
window.addEventListener('DOMContentLoaded',()=>{
  const enableEditing = window.innerWidth >= 768;
  let pendingSaves=0;
  const statusEl=document.getElementById('saveStatus');
  const statusDefaults={pending:'保存中…',success:'已自动保存',error:'保存失败，请稍后重试'};
  let statusTimer=null;
  let commonItems=Array.isArray(window.commonTodoItems)?window.commonTodoItems.map(item=>({id:item.id,content:item.content??''})):[];
  let commonContentSet=new Set();
  let commonContentList=[];
  let suggestionBar=null;
  let suggestionList=null;
  let suggestionCurrentInput=null;
  let suggestionHideTimer=null;
  let suggestionInteracting=false;
  const commonListEl=document.getElementById('commonItemsList');
  const commonEmptyEl=document.getElementById('commonItemsEmpty');
  const addCommonBtn=document.getElementById('addCommonItem');
  const undoBanner=document.getElementById('undoDeleteBanner');
  const undoButton=undoBanner?.querySelector('.undo-button');
  const undoCountdown=undoBanner?.querySelector('.countdown');
  const undoDuration=6000;
  let pendingDeletion=null;
  let undoTimer=null;
  let countdownTimer=null;

  function rebuildCommonContentSet(){
    const uniqueMap=new Map();
    commonItems.forEach(item=>{
      const text=String(item.content||'').trim();
      if(!text) return;
      const lower=text.toLocaleLowerCase();
      if(!uniqueMap.has(lower)){
        uniqueMap.set(lower,{text,lower});
      }
    });
    commonContentList=Array.from(uniqueMap.values());
    commonContentSet=new Set(commonContentList.map(entry=>entry.lower));
  }
  rebuildCommonContentSet();

  const htmlEscapeMap={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'};
  function escapeHtml(str){
    return String(str).replace(/[&<>"']/g,ch=>htmlEscapeMap[ch]||ch);
  }

  function prepareInlineHighlight(input){
    if(!input || input.dataset.inlineHighlight==='1') return;
    const parent=input.parentElement;
    if(!parent) return;
    const wrapper=document.createElement('div');
    wrapper.className='todo-input-wrapper';
    if(input.classList.contains('flex-grow-1')){
      wrapper.classList.add('flex-grow-1');
      input.classList.remove('flex-grow-1');
    }
    if(input.classList.contains('me-2')){
      wrapper.classList.add('me-2');
      input.classList.remove('me-2');
    }
    const highlightLayer=document.createElement('div');
    highlightLayer.className='todo-highlight-layer';
    highlightLayer.setAttribute('aria-hidden','true');
    const highlightContent=document.createElement('span');
    highlightContent.className='todo-highlight-content';
    highlightLayer.appendChild(highlightContent);
    parent.insertBefore(wrapper,input);
    wrapper.appendChild(highlightLayer);
    wrapper.appendChild(input);
    input.dataset.inlineHighlight='1';
    input._highlightContent=highlightContent;
    const sync=()=>syncHighlightScroll(input);
    ['scroll','focus','click','keyup'].forEach(evt=>input.addEventListener(evt,sync));
    sync();
  }

  function syncHighlightScroll(input){
    if(!input) return;
    const highlightContent=input._highlightContent;
    if(highlightContent){
      const offset=Number(input.scrollLeft)||0;
      highlightContent.style.transform=`translateX(${-offset}px)`;
    }
  }

  function buildHighlightRanges(value,matches){
    if(!value || !matches || !matches.length) return [];
    const lower=value.toLocaleLowerCase();
    const ranges=[];
    matches.forEach(entry=>{
      const target=entry?.lower;
      if(!target) return;
      let startIndex=0;
      while(startIndex<=lower.length){
        const found=lower.indexOf(target,startIndex);
        if(found===-1) break;
        ranges.push({start:found,end:found+target.length});
        startIndex=found+target.length;
      }
    });
    if(!ranges.length) return [];
    ranges.sort((a,b)=>a.start-b.start || b.end-a.end);
    const merged=[];
    ranges.forEach(range=>{
      if(!merged.length){
        merged.push({...range});
        return;
      }
      const last=merged[merged.length-1];
      if(range.start<=last.end){
        last.end=Math.max(last.end,range.end);
      }else{
        merged.push({...range});
      }
    });
    return merged;
  }

  function updateInlineHighlight(input,matches){
    if(!input) return;
    const highlightContent=input._highlightContent;
    if(!highlightContent) return;
    const value=String(input.value||'');
    let html='';
    const ranges=buildHighlightRanges(value,matches);
    if(ranges.length){
      let cursor=0;
      ranges.forEach(range=>{
        if(range.start>cursor){
          html+=escapeHtml(value.slice(cursor,range.start));
        }
        html+=`<mark>${escapeHtml(value.slice(range.start,range.end))}</mark>`;
        cursor=range.end;
      });
      if(cursor<value.length){
        html+=escapeHtml(value.slice(cursor));
      }
    }
    if(!html){
      html=escapeHtml(value);
    }
    highlightContent.innerHTML=html || '&#8203;';
    syncHighlightScroll(input);
  }

  function highlightItem(input){
    if(!input) return;
    prepareInlineHighlight(input);
    const li=input.closest('li');
    if(!li) return;
    const rawValue=String(input.value||'');
    const trimmedValue=rawValue.trim();
    const lowerValue=rawValue.toLocaleLowerCase();
    const matches=commonContentList.filter(entry=>lowerValue.includes(entry.lower));
    if(matches.length){
      input.classList.add('todo-common-match');
      const lang=document.documentElement.lang||'zh';
      const joiner=lang && lang.toLowerCase().startsWith('en') ? ', ' : '、';
      const uniqueLabels=Array.from(new Map(matches.map(entry=>[entry.lower,entry.text])).values());
      const joinedLabel=uniqueLabels.join(joiner);
      const hintKey=uniqueLabels.length>1 ? 'todolist.common.match_hint_plural' : 'todolist.common.match_hint_single';
      const fallback=uniqueLabels.length>1 ? `Matches common items: ${joinedLabel}` : `Matches common item: ${joinedLabel}`;
      const hint=getLocalizedText(hintKey,fallback,{items:joinedLabel,item:joinedLabel});
      if(hint){
        input.setAttribute('title',hint);
      }
    }else{
      input.classList.remove('todo-common-match');
      input.removeAttribute('title');
    }
    if(trimmedValue && commonContentSet.has(trimmedValue.toLocaleLowerCase())){
      li.classList.add('todo-common-highlight');
    }else{
      li.classList.remove('todo-common-highlight');
    }
    updateInlineHighlight(input,matches);
  }

  function refreshCommonHighlights(){
    document.querySelectorAll('.todolist .item-content').forEach(input=>highlightItem(input));
  }

  function ensureSuggestionBar(){
    if(!enableEditing) return;
    if(suggestionBar) return;
    suggestionBar=document.createElement('div');
    suggestionBar.className='common-suggestion-bar';
    suggestionBar.innerHTML=`<div class="common-suggestion-inner"><div class="common-suggestion-header" data-i18n="todolist.common.suggestions">常用事项候选</div><div class="common-suggestion-list" role="list"></div></div>`;
    suggestionList=suggestionBar.querySelector('.common-suggestion-list');
    document.body.appendChild(suggestionBar);
    if(typeof applyTranslations==='function'){
      applyTranslations();
    }
    suggestionBar.addEventListener('mousedown',evt=>{
      evt.preventDefault();
      suggestionInteracting=true;
    });
    suggestionBar.addEventListener('mouseup',()=>{
      suggestionInteracting=false;
    });
  }

  function renderCommonSuggestions(){
    ensureSuggestionBar();
    if(!suggestionList) return 0;
    suggestionList.innerHTML='';
    if(commonItems.length===0){
      if(suggestionBar){
        suggestionBar.dataset.empty='1';
      }
      return 0;
    }
    const currentValue=suggestionCurrentInput?String(suggestionCurrentInput.value||'') : '';
    const currentLower=currentValue.toLocaleLowerCase();
    const availableItems=commonItems.filter(item=>{
      const text=String(item.content||'').trim();
      if(!text) return false;
      if(!suggestionCurrentInput) return true;
      return !currentLower.includes(text.toLocaleLowerCase());
    });
    if(!availableItems.length){
      return 0;
    }
    if(suggestionBar){
      suggestionBar.dataset.empty='0';
    }
    availableItems.forEach(item=>{
      const btn=document.createElement('button');
      btn.type='button';
      btn.className='common-suggestion-pill';
      btn.textContent=item.content;
      btn.addEventListener('click',()=>{
        if(!suggestionCurrentInput) return;
        insertCommonText(suggestionCurrentInput,item.content||'');
        suggestionCurrentInput.focus();
      });
      suggestionList.appendChild(btn);
    });
    return availableItems.length;
  }

  function updateSuggestionPosition(){
    if(!suggestionBar || !suggestionCurrentInput || suggestionBar.style.display==='none') return;
    const rect=suggestionCurrentInput.getBoundingClientRect();
    const gap=8;
    const minWidth=240;
    const maxWidth=Math.min(520,window.innerWidth-gap*2);
    const availableWidth=Math.min(Math.max(rect.width,minWidth),maxWidth);
    const maxLeft=window.innerWidth-availableWidth-gap;
    const desiredLeft=Math.max(gap,Math.min(rect.left,maxLeft));
    suggestionBar.style.width=`${availableWidth}px`;
    suggestionBar.style.left=`${desiredLeft}px`;
    suggestionBar.style.top=`${rect.bottom+gap}px`;
  }

  function showCommonSuggestionBar(input){
    if(!enableEditing) return;
    clearTimeout(suggestionHideTimer);
    suggestionCurrentInput=input;
    const count=renderCommonSuggestions();
    if(!suggestionBar || !count){
      if(suggestionBar){
        suggestionBar.style.display='none';
        suggestionBar.dataset.visible='0';
      }
      return;
    }
    suggestionBar.style.display='block';
    suggestionBar.dataset.visible='1';
    updateSuggestionPosition();
  }

  function hideCommonSuggestionBar(){
    if(!suggestionBar) return;
    suggestionBar.style.display='none';
    suggestionBar.dataset.visible='0';
    suggestionCurrentInput=null;
  }

  function scheduleHideSuggestionBar(){
    clearTimeout(suggestionHideTimer);
    suggestionHideTimer=setTimeout(()=>{
      if(suggestionInteracting){
        suggestionInteracting=false;
        return;
      }
      hideCommonSuggestionBar();
    },140);
  }

  function insertCommonText(input,text){
    const value=String(input.value||'');
    const start=input.selectionStart ?? value.length;
    const end=input.selectionEnd ?? value.length;
    const before=value.slice(0,start);
    const after=value.slice(end);
    const newValue=before+text+after;
    input.value=newValue;
    const cursor=start+text.length;
    if(typeof input.setSelectionRange==='function'){
      input.setSelectionRange(cursor,cursor);
    }
    input.dispatchEvent(new Event('input',{bubbles:true}));
  }

  function renderCommonManagerList(){
    if(!commonListEl) return;
    commonListEl.innerHTML='';
    commonItems.forEach((item,index)=>{
      commonListEl.appendChild(buildCommonItemRow(item,index,false));
    });
    if(commonEmptyEl){
      commonEmptyEl.dataset.visible=commonItems.length? 'false':'true';
    }
    if(typeof applyTranslations==='function'){
      applyTranslations();
    }
  }

  function buildCommonItemRow(item,index,isDraft){
    const li=document.createElement('li');
    li.className='list-group-item common-item-row';
    li.dataset.id=item.id ?? '';
    const indexEl=document.createElement('span');
    indexEl.className='common-item-index';
    indexEl.textContent=isDraft?'+':String(index+1);
    li.appendChild(indexEl);
    const input=document.createElement('input');
    input.type='text';
    input.maxLength=255;
    input.className='form-control form-control-sm common-item-input';
    input.value=item.content||'';
    input.setAttribute('data-i18n-attr','placeholder:todolist.common.placeholder');
    input.placeholder='请输入常用事项';
    li.appendChild(input);
    const btnGroup=document.createElement('div');
    btnGroup.className='btn-group btn-group-sm';
    const saveBtn=document.createElement('button');
    saveBtn.type='button';
    saveBtn.className='btn btn-primary save-common-item';
    saveBtn.setAttribute('data-i18n','todolist.common.save');
    saveBtn.textContent='保存';
    const deleteBtn=document.createElement('button');
    deleteBtn.type='button';
    deleteBtn.className='btn btn-outline-danger delete-common-item';
    deleteBtn.setAttribute('data-i18n','todolist.common.delete');
    deleteBtn.textContent='删除';
    btnGroup.append(saveBtn,deleteBtn);
    li.appendChild(btnGroup);
    input.addEventListener('input',()=>{
      input.classList.remove('is-invalid');
    });
    input.addEventListener('keydown',evt=>{
      if(evt.key==='Enter' && !evt.shiftKey){
        evt.preventDefault();
        saveBtn.click();
      }
    });
    saveBtn.addEventListener('click',()=>{
      const value=input.value.trim();
      if(!value){
        input.classList.add('is-invalid');
        input.focus();
        return;
      }
      if(isDraft){
        postData({action:'common_create',content:value,sort_order:commonItems.length})
          .then(r=>r.json())
          .then(j=>{
            commonItems.push({id:j.id,content:value});
            rebuildCommonContentSet();
            renderCommonManagerList();
            renderCommonSuggestions();
            refreshCommonHighlights();
          });
      }else{
        postData({action:'common_update',id:item.id,content:value})
          .then(()=>{
            const target=commonItems.find(ci=>String(ci.id)===String(item.id));
            if(target){
              target.content=value;
            }
            rebuildCommonContentSet();
            renderCommonManagerList();
            renderCommonSuggestions();
            refreshCommonHighlights();
          });
      }
    });
    deleteBtn.addEventListener('click',()=>{
      if(isDraft){
        li.remove();
        if(commonEmptyEl && !commonItems.length && !commonListEl.children.length){
          commonEmptyEl.dataset.visible='true';
        }
        return;
      }
      postData({action:'common_delete',id:item.id})
        .then(()=>{
          commonItems=commonItems.filter(ci=>String(ci.id)!==String(item.id));
          rebuildCommonContentSet();
          renderCommonManagerList();
          renderCommonSuggestions();
          refreshCommonHighlights();
        });
    });
    return li;
  }

  function appendDraftCommonItem(){
    if(!commonListEl) return;
    const draftRow=buildCommonItemRow({id:null,content:''},commonItems.length,true);
    commonListEl.appendChild(draftRow);
    if(commonEmptyEl){
      commonEmptyEl.dataset.visible='false';
    }
    if(typeof applyTranslations==='function'){
      applyTranslations();
    }
    const input=draftRow.querySelector('.common-item-input');
    if(input){
      input.focus();
    }
  }

  if(addCommonBtn){
    addCommonBtn.addEventListener('click',appendDraftCommonItem);
  }

  if(undoButton){
    undoButton.addEventListener('click',()=>{
      if(pendingDeletion){
        restoreDeletedItem(pendingDeletion);
      }
    });
  }

  window.addEventListener('resize',updateSuggestionPosition);
  window.addEventListener('scroll',updateSuggestionPosition,true);
  renderCommonManagerList();
  renderCommonSuggestions();
  function getLocalizedText(key,fallback='',params){
    const lang=document.documentElement.lang||'zh';
    let template='';
    if(typeof translations!=='undefined'){
      template=translations[lang]?.[key] ?? translations.zh?.[key] ?? '';
    }
    if(!template) template=fallback||'';
    if(params && template){
      Object.keys(params).forEach(paramKey=>{
        const value=params[paramKey];
        template=template.replace(new RegExp(`\\{${paramKey}\\}`,'g'), value);
      });
    }
    return template;
  }

  function getStatusMessage(state){
    const key='todolist.status.'+state;
    return getLocalizedText(key,statusDefaults[state]||'');
  }
  function hideStatus(){
    if(!statusEl) return;
    statusEl.style.display='none';
    statusEl.setAttribute('aria-hidden','true');
  }
  function showStatus(state){
    if(!statusEl) return;
    const message=getStatusMessage(state);
    statusEl.dataset.state=state;
    statusEl.querySelector('.status-text').textContent=message;
    statusEl.style.display='flex';
    statusEl.setAttribute('aria-hidden','false');
    clearTimeout(statusTimer);
    if(state==='success'){
      statusTimer=setTimeout(hideStatus,2000);
    }else if(state==='error'){
      statusTimer=setTimeout(hideStatus,4000);
    }
  }
  function postData(data){
    pendingSaves++;
    showStatus('pending');
    let hadError=false;
    return fetch('todolist_save.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)})
      .then(response=>{if(!response.ok) throw new Error('Network response was not ok');return response;})
      .catch(err=>{console.error(err);hadError=true;showStatus('error');throw err;})
      .finally(()=>{
        pendingSaves=Math.max(0,pendingSaves-1);
        if(pendingSaves>0 && !hadError){
          showStatus('pending');
        }else if(pendingSaves===0 && !hadError){
          showStatus('success');
        }else if(pendingSaves===0 && hadError){
          // allow error message to linger before hiding automatically
        }
      });
  }
  function updateStats(){
    ['work','personal','longterm'].forEach(cat=>{
      const lists=document.querySelectorAll(`.todolist[data-category='${cat}']`);
      let total=0,done=0;
      lists.forEach(l=>{
        l.querySelectorAll('li').forEach(li=>{
          total++;
          if(li.querySelector('.item-done').checked) done++;
        });
      });
      const span=document.querySelector(`h3[data-category='${cat}'] .stats`);
      if(span) span.textContent=`(${done}/${total})`;
    });
  }
  function updateUndoCountdownDisplay(remainingMs){
    if(!undoCountdown) return;
    const seconds=Math.max(1,Math.ceil(remainingMs/1000));
    undoCountdown.textContent=getLocalizedText('todolist.delete.undo_countdown','Undo in {seconds}s',{seconds});
  }
  function hideUndoBanner(){
    if(!undoBanner) return;
    undoBanner.style.display='none';
    undoBanner.setAttribute('aria-hidden','true');
    if(undoCountdown) undoCountdown.textContent='';
  }
  function finalizeDeletion(entry){
    clearTimeout(undoTimer);
    clearInterval(countdownTimer);
    if(entry && entry.id){
      postData({action:'delete',id:entry.id}).then(()=>updateStats());
    }
    pendingDeletion=null;
    hideUndoBanner();
  }
  function restoreDeletedItem(entry){
    clearTimeout(undoTimer);
    clearInterval(countdownTimer);
    if(!entry || !entry.list || !entry.element) return;
    const children=Array.from(entry.list.children);
    const anchor=entry.position < children.length ? children[entry.position] : null;
    entry.list.insertBefore(entry.element,anchor);
    saveItem(entry.element);
    updateStats();
    pendingDeletion=null;
    hideUndoBanner();
  }
  function showUndoBanner(){
    if(!undoBanner) return;
    undoBanner.style.display='flex';
    undoBanner.setAttribute('aria-hidden','false');
    if(typeof applyTranslations==='function'){
      applyTranslations();
    }
    updateUndoCountdownDisplay(undoDuration);
    const start=Date.now();
    clearInterval(countdownTimer);
    countdownTimer=setInterval(()=>{
      const elapsed=Date.now()-start;
      const remaining=Math.max(0,undoDuration-elapsed);
      updateUndoCountdownDisplay(remaining);
      if(remaining<=0){
        clearInterval(countdownTimer);
      }
    },200);
  }
  function startPendingDeletion(li){
    if(!li) return;
    const list=li.parentElement;
    const id=li.dataset.id;
    if(pendingDeletion){
      finalizeDeletion(pendingDeletion);
    }
    const position=Array.from(list.children).indexOf(li);
    list.removeChild(li);
    pendingDeletion={id,list,position,element:li};
    updateStats();
    if(!id){
      pendingDeletion=null;
      hideUndoBanner();
      return;
    }
    showUndoBanner();
    undoTimer=setTimeout(()=>{
      finalizeDeletion({id});
    },undoDuration);
  }
  const multilineThreshold=48;
  function shouldExpandMultiline(input){
    if(!input) return false;
    const value=String(input.value||'');
    return value.length>multilineThreshold || value.includes('\n');
  }
  function expandMultiline(input){
    if(!input) return;
    input.dataset.multiline='1';
    input.classList.add('is-multiline');
    input.rows=Math.min(8,Math.max(2,(input.value.split('\n').length||1)));
    input.style.height='auto';
    input.style.height=Math.min(320,input.scrollHeight+4)+'px';
    const wrapper=input.closest('.todo-input-wrapper');
    if(wrapper){
      wrapper.classList.add('is-multiline');
    }
  }
  function collapseMultiline(input){
    if(!input) return;
    input.dataset.multiline='0';
    input.classList.remove('is-multiline');
    input.rows=1;
    input.style.height='';
    const wrapper=input.closest('.todo-input-wrapper');
    if(wrapper){
      wrapper.classList.remove('is-multiline');
    }
  }
  function adjustMultiline(input){
    if(!input) return;
    if(shouldExpandMultiline(input)){
      expandMultiline(input);
    }
    if(input.dataset.multiline==='1'){
      input.style.height='auto';
      input.style.height=Math.min(320,input.scrollHeight+4)+'px';
    }
  }
  function saveItem(li){
    const id=li.dataset.id;
    const content=li.querySelector('.item-content').value;
    const done=li.querySelector('.item-done').checked;
    const list=li.parentElement;
    const data={action:'update',id:id,content:content,is_done:done,category:list.dataset.category,day:list.dataset.day,week_start:'<?= $week_start; ?>'};
    if(id){
      postData(data).then(()=>updateStats());
      return;
    }
    if(li.dataset.creating==='1'){
      li.dataset.needsResave='1';
      return;
    }
    li.dataset.creating='1';
    postData(data)
      .then(r=>r.json())
      .then(j=>{
        li.dataset.id=j.id;
        const needsResave=li.dataset.needsResave==='1';
        delete li.dataset.creating;
        delete li.dataset.needsResave;
        if(needsResave){
          saveItem(li);
        } else {
          updateStats();
        }
      })
      .catch(()=>{
        delete li.dataset.creating;
        delete li.dataset.needsResave;
      });
  }
  function attach(li){
    const content=li.querySelector('.item-content');
    prepareInlineHighlight(content);
    collapseMultiline(content);
    if(enableEditing){
      content.addEventListener('input',()=>{
        saveItem(li);
        highlightItem(content);
        adjustMultiline(content);
        if(document.activeElement===content){
          showCommonSuggestionBar(content);
        }
      });
      content.addEventListener('focus',()=>{showCommonSuggestionBar(content);highlightItem(content);adjustMultiline(content);});
      content.addEventListener('blur',()=>{scheduleHideSuggestionBar();collapseMultiline(content);});
    }else{
      content.setAttribute('readonly',true);
    }
    li.querySelector('.item-done').addEventListener('change',()=>{saveItem(li);highlightItem(content);});
    if(enableEditing){
      const copyBtn=li.querySelector('.copy-item');
      if(copyBtn) copyBtn.addEventListener('click',()=>copyText(content.value));
      const nextBtn=li.querySelector('.next-week-item');
      if(nextBtn) nextBtn.addEventListener('click',()=>{postData({action:'copy_item_next',id:li.dataset.id,week_start:'<?= $week_start; ?>'});});
      const tomorrowBtn=li.querySelector('.tomorrow-item');
      if(tomorrowBtn) tomorrowBtn.addEventListener('click',()=>{
        const list=li.parentElement;
        const day=list.dataset.day;
        if(day==='sun'){
          postData({action:'tomorrow',id:li.dataset.id,day:day,week_start:'<?= $week_start; ?>'}).then(()=>{li.remove();updateStats();});
        }else{
          const order=['mon','tue','wed','thu','fri','sat','sun'];
          const nextDay=order[order.indexOf(day)+1];
          const target=document.querySelector(`.todolist[data-category='${list.dataset.category}'][data-day='${nextDay}']`);
          target.appendChild(li);
          saveItem(li);
          const orderArr=Array.from(target.children).map((li,i)=>({id:li.dataset.id,position:i}));
          postData({action:'order',order:orderArr});
          updateStats();
        }
      });
      li.querySelector('.delete-item').addEventListener('click',()=>startPendingDeletion(li));
    }else{
      li.querySelectorAll('.drag-handle,.copy-item,.next-week-item,.tomorrow-item,.delete-item').forEach(btn=>btn.style.display='none');
    }
    highlightItem(content);
  }
  document.querySelectorAll('.todolist').forEach(list=>{
    if(enableEditing){
      Sortable.create(list,{
        group:'todolist',
        animation:150,
        handle:'.drag-handle',
        onChoose:function(){
          hideCommonSuggestionBar();
        },
        onStart:function(){
          hideCommonSuggestionBar();
        },
        onEnd:function(evt){
          saveItem(evt.item);
          const lists=new Set([evt.from,evt.to]);
          lists.forEach(l=>{
            const order=Array.from(l.children).map((li,i)=>({id:li.dataset.id,position:i}));
            postData({action:'order',order:order});
          });
          updateStats();
        }
      });
    }
    list.querySelectorAll('li').forEach(attach);
  });
  document.querySelectorAll('.add-item').forEach(btn=>{
    if(enableEditing){
      btn.addEventListener('click',()=>{
        const list=document.querySelector(`.todolist[data-category='${btn.dataset.category}'][data-day='${btn.dataset.day}']`);
        const li=document.createElement('li');
        li.className='list-group-item d-flex align-items-center flex-nowrap';
        const dragHandleHtml = `
          <button type="button" class="btn btn-sm btn-outline-secondary me-2 drag-handle icon-btn" title="拖动排序" data-i18n-title="todolist.drag_handle" aria-label="拖动排序">
            <span class="visually-hidden" data-i18n="todolist.drag_handle">拖动排序</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
              <path d="M3 5h10M3 8h10M3 11h10" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"></path>
            </svg>
          </button>`;
        const copyBtnHtml = `
          <button class="btn btn-sm btn-outline-secondary ms-auto copy-item icon-btn" title="复制" data-i18n-title="todolist.copy_item" aria-label="复制">
            <span class="visually-hidden" data-i18n="todolist.copy_item">复制</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
              <rect x="5.2" y="4.2" width="7.6" height="9.6" rx="1.3" ry="1.3" fill="none" stroke="currentColor" stroke-width="1.1"></rect>
              <rect x="3.2" y="2.2" width="7.6" height="9.6" rx="1.3" ry="1.3" fill="none" stroke="currentColor" stroke-width="1.1"></rect>
              <path d="M6.8 2.9h2.4" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
            </svg>
          </button>`;
        const nextWeekBtnHtml = `
          <button class="btn btn-sm btn-secondary ms-2 next-week-item icon-btn" title="鸽下周" data-i18n-title="todolist.copy_next" aria-label="鸽下周">
            <span class="visually-hidden" data-i18n="todolist.copy_next">鸽下周</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
              <rect x="2.6" y="3.6" width="10.8" height="8.8" rx="1.2" ry="1.2" fill="none" stroke="currentColor" stroke-width="1.1"></rect>
              <path d="M5.3 2.5v2.2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
              <path d="M10.7 2.5v2.2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
              <path d="M4 6.8h8" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
              <path d="M6.2 9.7h3.2" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
              <path d="M8.6 8.3l1.9 1.4-1.9 1.4" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
          </button>`;
        const tomorrowBtnHtml = btn.dataset.day ? `
          <button class="btn btn-sm btn-outline-primary ms-2 tomorrow-item text-nowrap icon-btn" title="鸽明天" data-i18n-title="todolist.cut_tomorrow" aria-label="鸽明天">
            <span class="visually-hidden" data-i18n="todolist.cut_tomorrow">鸽明天</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
              <circle cx="8" cy="8" r="5.6" fill="none" stroke="currentColor" stroke-width="1.1"></circle>
              <path d="M6.5 8h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
              <path d="M8.5 6.4 10.2 8 8.5 9.6" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
          </button>` : '';
        li.innerHTML = `
          ${dragHandleHtml}
          <input type="checkbox" class="form-check-input me-2 item-done">
          <textarea class="form-control item-content flex-grow-1 me-2" rows="1" data-multiline="0"></textarea>
          ${copyBtnHtml}
          ${nextWeekBtnHtml}
          ${tomorrowBtnHtml}
          <button class="btn btn-sm btn-danger ms-2 delete-item">&times;</button>`;
        list.appendChild(li);
        applyTranslations();
        attach(li);
        saveItem(li);
      });
    }else{
      btn.style.display='none';
    }
  });
  document.querySelector("input[name='week']").addEventListener('change',function(){this.form.submit();});
  document.getElementById('copyNextWeek').addEventListener('click',()=>{
    postData({action:'copy_next',week_start:'<?= $week_start; ?>'})
      .then(()=>{window.location='todolist.php?week=<?= $next_week_param; ?>';});
  });
  refreshCommonHighlights();
  updateStats();
});

function printTodoList(){
  const lang=document.documentElement.lang||'zh';
  const totalItems=document.querySelectorAll('.todolist li').length;
  const fontSize=Math.max(8,14-totalItems*0.1); // shrink font when there are many items
  const weekStart='<?= date('Y.m.d', strtotime($week_start)); ?>';
  const weekEnd='<?= date('Y.m.d', strtotime($week_end)); ?>';
  let html='<html><head><title>'+document.title+'</title><style>'+
            '@page{size:A4;margin:10mm;}' +
            'body{font-family:sans-serif;margin:0;padding:0 5mm;background:#fff;font-size:'+fontSize+'pt;}' +
            'h1{text-align:center;margin:0 0 4mm 0;font-size:'+(fontSize+6)+'pt;}' +
            'h3{margin:2mm 0;font-size:'+(fontSize+3)+'pt;}' +
            'h4{margin:1mm 0;font-size:'+(fontSize+2)+'pt;}' +
            'h3.work,h3.personal,h3.longterm{display:inline-block;padding:2mm;border-radius:3px;}' +
            'h3.work{background:#e6f0ff;}' +
            'h3.personal{background:#e6ffe6;}' +
            'h3.longterm{background:#fff7e6;}' +
            'ul{list-style:none;padding-left:0;margin:0 0 2mm 0;}' +
            'li{margin:0;padding:0.2mm 1mm;font-size:'+(fontSize+1.5)+'pt;}' +
            'li strong{margin-right:1mm;}' +
            'div.columns{display:flex;}' +
            'div.columns>.left,div.columns>.right{width:50%;box-sizing:border-box;}' +
            'div.columns>.left{padding-right:1mm;}' +
            'div.columns>.right{padding-left:1mm;}' +
            '</style></head><body>';
  html+='<h1>待办事项 <small>'+weekStart+' - '+weekEnd+'</small></h1>';
  function renderCategory(cat){
    const lists=document.querySelectorAll(`.todolist[data-category='${cat}']`);
    if(Array.from(lists).every(l=>!l.children.length)) return '';
    const catKey='todolist.category.'+cat;
    let total=0,done=0;
    lists.forEach(l=>{
      l.querySelectorAll('li').forEach(li=>{total++;if(li.querySelector('.item-done').checked) done++;});
    });
    let catHtml='<h3 class="'+cat+'">'+(translations[lang][catKey]||'')+' ('+done+'/'+total+')</h3>';
    lists.forEach(list=>{
      if(!list.children.length) return;
      const day=list.dataset.day;
      if(day){
        const dayKey='todolist.days.'+day;
        catHtml+='<h4>'+(translations[lang][dayKey]||'')+'</h4>';
      }
      catHtml+='<ul class="'+cat+'">';
      list.querySelectorAll('li').forEach(li=>{
        const content=li.querySelector('.item-content').value
          .replace(/&/g,'&amp;')
          .replace(/</g,'&lt;')
          .replace(/>/g,'&gt;');
        const done=li.querySelector('.item-done').checked;
        catHtml+='<li>'+(done?'<strong>✓</strong> ':'<strong>□</strong> ')+content+'</li>';
      });
      catHtml+='</ul>';
    });
    return catHtml;
  }
  const leftCol=renderCategory('work');
  const rightCol=renderCategory('personal')+renderCategory('longterm');
  html+='<div class="columns"><div class="left">'+leftCol+'</div><div class="right">'+rightCol+'</div></div>';
  html+='</body></html>';
  const w=window.open('','_blank');
  w.document.write(html);
  w.document.close();
  w.focus();
  w.print();
  w.close();
}
</script>
<?php include 'footer.php'; ?>
