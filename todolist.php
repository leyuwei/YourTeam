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
.todolist li .item-content{flex:1 1 auto;min-width:0;margin-right:0.5rem;}
.todolist li .copy-item{margin-left:auto;}
.todolist li .copy-item,
.todolist li .next-week-item{white-space:nowrap;}
.today-heading{background:var(--app-highlight-bg);color:var(--app-highlight-text);padding:2px 4px;border-radius:4px;}
.today-heading .btn{color:inherit;border-color:var(--app-highlight-border);}
.today-heading .btn:hover,.today-heading .btn:focus{background-color:var(--app-highlight-button-hover);color:var(--app-text-color);}
.todolist.today{border-left:4px solid var(--app-highlight-border);padding-left:4px;background:var(--app-highlight-surface);}
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
        <input type="checkbox" class="form-check-input me-2 item-done" <?= $it['is_done']?'checked':''; ?>>
        <input type="text" class="form-control item-content flex-grow-1 me-2" value="<?= htmlspecialchars($it['content']); ?>">
        <button class="btn btn-sm btn-outline-secondary ms-auto copy-item" data-i18n="todolist.copy_item">复制</button>
        <button class="btn btn-sm btn-secondary ms-2 next-week-item" data-i18n="todolist.copy_next">鸽下周</button>
        <button class="btn btn-sm btn-outline-primary ms-2 tomorrow-item text-nowrap" data-i18n="todolist.cut_tomorrow">鸽明天</button>
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
        <input type="checkbox" class="form-check-input me-2 item-done" <?= $it['is_done']?'checked':''; ?>>
        <input type="text" class="form-control item-content flex-grow-1 me-2" value="<?= htmlspecialchars($it['content']); ?>">
        <button class="btn btn-sm btn-outline-secondary ms-auto copy-item" data-i18n="todolist.copy_item">复制</button>
        <button class="btn btn-sm btn-secondary ms-2 next-week-item" data-i18n="todolist.copy_next">鸽下周</button>
        <button class="btn btn-sm btn-outline-primary ms-2 tomorrow-item text-nowrap" data-i18n="todolist.cut_tomorrow">鸽明天</button>
        <button class="btn btn-sm btn-danger ms-2 delete-item">&times;</button>
      </li>
      <?php endforeach; endif; ?>
    </ul>
    <?php endforeach; ?>
    <h3 data-category="longterm"><b data-i18n="todolist.category.longterm">长期</b> <small class="stats">(<?= $stats['longterm']['done']; ?>/<?= $stats['longterm']['total']; ?>)</small> <button type="button" class="btn btn-sm btn-outline-success add-item" data-category="longterm" data-day="">+</button></h3>
    <ul class="list-group mb-3 todolist" data-category="longterm" data-day="">
      <?php if(!empty($items['longterm'][''])): foreach($items['longterm'][''] as $it): ?>
      <li class="list-group-item d-flex align-items-center flex-nowrap" data-id="<?= $it['id']; ?>">
        <input type="checkbox" class="form-check-input me-2 item-done" <?= $it['is_done']?'checked':''; ?>>
        <input type="text" class="form-control item-content flex-grow-1 me-2" value="<?= htmlspecialchars($it['content']); ?>">
        <button class="btn btn-sm btn-outline-secondary ms-auto copy-item" data-i18n="todolist.copy_item">复制</button>
        <button class="btn btn-sm btn-secondary ms-2 next-week-item" data-i18n="todolist.copy_next">鸽下周</button>
        <button class="btn btn-sm btn-danger ms-2 delete-item">&times;</button>
      </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>
</div>
<div id="saveHint" class="position-fixed bottom-0 end-0 p-3 bg-success text-white rounded" style="display:none;z-index:1080;">已自动保存</div>
<div id="unsavedWarning" class="position-fixed bottom-0 start-0 p-3 bg-warning text-dark rounded" style="display:none;z-index:1080;">有未保存内容</div>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
window.addEventListener('DOMContentLoaded',()=>{
  const enableEditing = window.innerWidth >= 768;
  let pendingSaves=0;
  function showHint(){
    const hint=document.getElementById('saveHint');
    hint.style.display='block';
    clearTimeout(hint.dataset.t);
    hint.dataset.t=setTimeout(()=>{hint.style.display='none';},2000);
  }
  function updateWarning(){
    const warn=document.getElementById('unsavedWarning');
    warn.style.display=pendingSaves>0?'block':'none';
  }
  function postData(data){
    pendingSaves++;
    updateWarning();
    return fetch('todolist_save.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)})
      .finally(()=>{pendingSaves--;showHint();updateWarning();});
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
  function saveItem(li){
    const id=li.dataset.id;
    const content=li.querySelector('.item-content').value;
    const done=li.querySelector('.item-done').checked;
    const list=li.parentElement;
    const data={action:'update',id:id,content:content,is_done:done,category:list.dataset.category,day:list.dataset.day,week_start:'<?= $week_start; ?>'};
    postData(data).then(r=>r.json()).then(j=>{if(!id)li.dataset.id=j.id;}).then(()=>updateStats());
  }
  function attach(li){
    const content=li.querySelector('.item-content');
    if(enableEditing){
      content.addEventListener('input',()=>saveItem(li));
    }else{
      content.setAttribute('readonly',true);
    }
    li.querySelector('.item-done').addEventListener('change',()=>saveItem(li));
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
      li.querySelector('.delete-item').addEventListener('click',()=>{postData({action:'delete',id:li.dataset.id}).then(()=>{li.remove();updateStats();});});
    }else{
      li.querySelectorAll('.copy-item,.next-week-item,.tomorrow-item,.delete-item').forEach(btn=>btn.style.display='none');
    }
  }
  document.querySelectorAll('.todolist').forEach(list=>{
    if(enableEditing){
      Sortable.create(list,{group:'todolist',animation:150,onEnd:function(evt){
        saveItem(evt.item);
        const lists=new Set([evt.from,evt.to]);
        lists.forEach(l=>{
          const order=Array.from(l.children).map((li,i)=>({id:li.dataset.id,position:i}));
          postData({action:'order',order:order});
        });
        updateStats();
      }});
    }
    list.querySelectorAll('li').forEach(attach);
  });
  document.querySelectorAll('.add-item').forEach(btn=>{
    if(enableEditing){
      btn.addEventListener('click',()=>{
        const list=document.querySelector(`.todolist[data-category='${btn.dataset.category}'][data-day='${btn.dataset.day}']`);
        const li=document.createElement('li');
        li.className='list-group-item d-flex align-items-center flex-nowrap';
        const tomorrowBtn = btn.dataset.day ? '<button class="btn btn-sm btn-outline-primary ms-2 tomorrow-item text-nowrap" data-i18n="todolist.cut_tomorrow">鸽明天</button>' : '';
        li.innerHTML=`<input type="checkbox" class="form-check-input me-2 item-done"><input type="text" class="form-control item-content flex-grow-1 me-2"><button class="btn btn-sm btn-outline-secondary ms-auto copy-item" data-i18n="todolist.copy_item">复制</button><button class="btn btn-sm btn-secondary ms-2 next-week-item" data-i18n="todolist.copy_next">复制到下周</button>${tomorrowBtn}<button class="btn btn-sm btn-danger ms-2 delete-item">&times;</button>`;
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
