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
$week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
$next_week_param = date('o-\\WW', strtotime($week_start . ' +7 days'));
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
?>
<style>
.todolist li{flex-wrap:nowrap;}
.todolist li .item-content{flex:1 1 auto;min-width:0;}
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
</style>
<h2 class="text-center"><span data-i18n="todolist.title">待办事项</span> @ <?= date('Y.m.d', strtotime($week_start)) ?> - <?= date('Y.m.d', strtotime($week_end)) ?></small></h2>
<?= $week_hint; ?>
<form method="get" class="mb-3 d-flex flex-wrap align-items-center gap-2">
  <input type="week" name="week" class="form-control form-control-lg w-auto" value="<?= htmlspecialchars($week_param); ?>">
  <a class="btn btn-success" href="todolist_export.php?week=<?= urlencode($week_param); ?>" data-i18n="todolist.export">导出</a>
  <button type="button" class="btn btn-secondary" id="copyNextWeek" data-i18n="todolist.copy_next">复制到下周</button>
  <button type="button" class="btn btn-outline-primary" onclick="printTodoList()" data-i18n="todolist.print">打印</button>
</form>
<div class="row">
  <div class="col-md-6">
    <h3><b data-i18n="todolist.category.work">工作</b></h3>
    <?php foreach($days as $k=>$label): ?>
    <h5><span data-i18n="todolist.days.<?= $k ?>"><?= $label; ?></span> <button type="button" class="btn btn-sm btn-outline-success add-item" data-category="work" data-day="<?= $k; ?>">+</button></h5>
    <ul class="list-group mb-3 todolist" data-category="work" data-day="<?= $k; ?>">
      <?php if(!empty($items['work'][$k])): foreach($items['work'][$k] as $it): ?>
      <li class="list-group-item d-flex align-items-center flex-nowrap" data-id="<?= $it['id']; ?>">
        <input type="checkbox" class="form-check-input me-2 item-done" <?= $it['is_done']?'checked':''; ?>>
        <input type="text" class="form-control item-content flex-grow-1" value="<?= htmlspecialchars($it['content']); ?>">
        <button class="btn btn-sm btn-outline-secondary ms-2 copy-item" data-i18n="todolist.copy_item">复制</button>
        <button class="btn btn-sm btn-secondary ms-2 next-week-item" data-i18n="todolist.copy_next">复制到下周</button>
        <button class="btn btn-sm btn-danger ms-2 delete-item">&times;</button>
      </li>
      <?php endforeach; endif; ?>
    </ul>
    <?php endforeach; ?>
  </div>
  <div class="col-md-6">
    <h3><b data-i18n="todolist.category.personal">私人</b></h3>
    <?php foreach($days as $k=>$label): ?>
    <h5><span data-i18n="todolist.days.<?= $k ?>"><?= $label; ?></span> <button type="button" class="btn btn-sm btn-outline-success add-item" data-category="personal" data-day="<?= $k; ?>">+</button></h5>
    <ul class="list-group mb-3 todolist" data-category="personal" data-day="<?= $k; ?>">
      <?php if(!empty($items['personal'][$k])): foreach($items['personal'][$k] as $it): ?>
      <li class="list-group-item d-flex align-items-center flex-nowrap" data-id="<?= $it['id']; ?>">
        <input type="checkbox" class="form-check-input me-2 item-done" <?= $it['is_done']?'checked':''; ?>>
        <input type="text" class="form-control item-content flex-grow-1" value="<?= htmlspecialchars($it['content']); ?>">
        <button class="btn btn-sm btn-outline-secondary ms-2 copy-item" data-i18n="todolist.copy_item">复制</button>
        <button class="btn btn-sm btn-secondary ms-2 next-week-item" data-i18n="todolist.copy_next">复制到下周</button>
        <button class="btn btn-sm btn-danger ms-2 delete-item">&times;</button>
      </li>
      <?php endforeach; endif; ?>
    </ul>
    <?php endforeach; ?>
    <h3><b data-i18n="todolist.category.longterm">长期</b> <button type="button" class="btn btn-sm btn-outline-success add-item" data-category="longterm" data-day="">+</button></h3>
    <ul class="list-group mb-3 todolist" data-category="longterm" data-day="">
      <?php if(!empty($items['longterm'][''])): foreach($items['longterm'][''] as $it): ?>
      <li class="list-group-item d-flex align-items-center flex-nowrap" data-id="<?= $it['id']; ?>">
        <input type="checkbox" class="form-check-input me-2 item-done" <?= $it['is_done']?'checked':''; ?>>
        <input type="text" class="form-control item-content flex-grow-1" value="<?= htmlspecialchars($it['content']); ?>">
        <button class="btn btn-sm btn-outline-secondary ms-2 copy-item" data-i18n="todolist.copy_item">复制</button>
        <button class="btn btn-sm btn-secondary ms-2 next-week-item" data-i18n="todolist.copy_next">复制到下周</button>
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
  function saveItem(li){
    const id=li.dataset.id;
    const content=li.querySelector('.item-content').value;
    const done=li.querySelector('.item-done').checked;
    const list=li.parentElement;
    const data={action:'update',id:id,content:content,is_done:done,category:list.dataset.category,day:list.dataset.day,week_start:'<?= $week_start; ?>'};
    postData(data).then(r=>r.json()).then(j=>{if(!id)li.dataset.id=j.id;});
  }
  function attach(li){
    li.querySelector('.item-content').addEventListener('input',()=>saveItem(li));
    li.querySelector('.item-done').addEventListener('change',()=>saveItem(li));
    const copyBtn=li.querySelector('.copy-item');
    if(copyBtn) copyBtn.addEventListener('click',()=>copyText(li.querySelector('.item-content').value));
    const nextBtn=li.querySelector('.next-week-item');
    if(nextBtn) nextBtn.addEventListener('click',()=>{postData({action:'copy_item_next',id:li.dataset.id,week_start:'<?= $week_start; ?>'});});
    li.querySelector('.delete-item').addEventListener('click',()=>{postData({action:'delete',id:li.dataset.id}).then(()=>li.remove());});
  }
  document.querySelectorAll('.todolist').forEach(list=>{
    Sortable.create(list,{group:'todolist',animation:150,onEnd:function(evt){
      saveItem(evt.item);
      const lists=new Set([evt.from,evt.to]);
      lists.forEach(l=>{
        const order=Array.from(l.children).map((li,i)=>({id:li.dataset.id,position:i}));
        postData({action:'order',order:order});
      });
    }});
    list.querySelectorAll('li').forEach(attach);
  });
  document.querySelectorAll('.add-item').forEach(btn=>{
    btn.addEventListener('click',()=>{
      const list=document.querySelector(`.todolist[data-category='${btn.dataset.category}'][data-day='${btn.dataset.day}']`);
      const li=document.createElement('li');
      li.className='list-group-item d-flex align-items-center flex-nowrap';
      li.innerHTML=`<input type="checkbox" class="form-check-input me-2 item-done"><input type="text" class="form-control item-content flex-grow-1"><button class="btn btn-sm btn-outline-secondary ms-2 copy-item" data-i18n="todolist.copy_item">复制</button><button class="btn btn-sm btn-secondary ms-2 next-week-item" data-i18n="todolist.copy_next">复制到下周</button><button class="btn btn-sm btn-danger ms-2 delete-item">&times;</button>`;
      list.appendChild(li);
      applyTranslations();
      attach(li);
      saveItem(li);
    });
  });
  document.querySelector("input[name='week']").addEventListener('change',function(){this.form.submit();});
  document.getElementById('copyNextWeek').addEventListener('click',()=>{
    postData({action:'copy_next',week_start:'<?= $week_start; ?>'})
      .then(()=>{window.location='todolist.php?week=<?= $next_week_param; ?>';});
  });
});

function printTodoList(){
  const lang=document.documentElement.lang||'en';
  let html='<html><head><title>'+document.title+'</title><style>'+
            'body{font-family:sans-serif;padding:10mm;background:#f9f9f9;}' +
            'h3{margin-top:10mm;}' +
            'ul{list-style:none;padding-left:0;}' +
            'li{margin:4px 0;padding:4px;border-radius:4px;background:#fff;}' +
            'li.done{text-decoration:line-through;color:#888;background:#e9ecef;}' +
            '</style></head><body>';
  html+="<h1>待办事项</h1>";
  document.querySelectorAll('.todolist').forEach(list=>{
    if(!list.children.length) return;
    const catKey='todolist.category.'+list.dataset.category;
    const dayKey=list.dataset.day? 'todolist.days.'+list.dataset.day : '';
    let header=translations[lang][catKey]||'';
    if(dayKey) header+=' - '+translations[lang][dayKey];
    html+='<h3>'+header+'</h3><ul>';
    list.querySelectorAll('li').forEach(li=>{
      const content=li.querySelector('.item-content').value;
      const done=li.querySelector('.item-done').checked;
      html+='<li class="'+(done?'done':'')+'">'+content+'</li>';
    });
    html+='</ul>';
  });
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
