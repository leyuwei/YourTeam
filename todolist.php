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
$days = ['mon'=>'周一','tue'=>'周二','wed'=>'周三','thu'=>'周四','fri'=>'周五','sat'=>'周六','sun'=>'周日'];
?>
<style>
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
<form method="get" class="mb-3">
  <input type="week" name="week" value="<?= htmlspecialchars($week_param); ?>">
  <button type="submit" class="btn btn-secondary btn-sm" data-i18n="todolist.switch_week">切换周</button>
  <a class="btn btn-success btn-sm" href="todolist_export.php?week=<?= urlencode($week_param); ?>" data-i18n="todolist.export">导出</a>
  <button type="button" class="btn btn-outline-primary btn-sm" onclick="printTodoList()" data-i18n="todolist.print">打印</button>
</form>
<div class="row">
  <div class="col-md-6">
    <h3><b data-i18n="todolist.category.work">工作</b></h3>
    <?php foreach($days as $k=>$label): ?>
    <h5><span data-i18n="todolist.days.<?= $k ?>"><?= $label; ?></span> <button type="button" class="btn btn-sm btn-outline-success add-item" data-category="work" data-day="<?= $k; ?>">+</button></h5>
    <ul class="list-group mb-3 todolist" data-category="work" data-day="<?= $k; ?>">
      <?php if(!empty($items['work'][$k])): foreach($items['work'][$k] as $it): ?>
      <li class="list-group-item d-flex align-items-center" data-id="<?= $it['id']; ?>">
        <input type="checkbox" class="form-check-input me-2 item-done" <?= $it['is_done']?'checked':''; ?>>
        <input type="text" class="form-control item-content" value="<?= htmlspecialchars($it['content']); ?>">
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
      <li class="list-group-item d-flex align-items-center" data-id="<?= $it['id']; ?>">
        <input type="checkbox" class="form-check-input me-2 item-done" <?= $it['is_done']?'checked':''; ?>>
        <input type="text" class="form-control item-content" value="<?= htmlspecialchars($it['content']); ?>">
        <button class="btn btn-sm btn-danger ms-2 delete-item">&times;</button>
      </li>
      <?php endforeach; endif; ?>
    </ul>
    <?php endforeach; ?>
    <h3><b data-i18n="todolist.category.longterm">长期</b> <button type="button" class="btn btn-sm btn-outline-success add-item" data-category="longterm" data-day="">+</button></h3>
    <ul class="list-group mb-3 todolist" data-category="longterm" data-day="">
      <?php if(!empty($items['longterm'][''])): foreach($items['longterm'][''] as $it): ?>
      <li class="list-group-item d-flex align-items-center" data-id="<?= $it['id']; ?>">
        <input type="checkbox" class="form-check-input me-2 item-done" <?= $it['is_done']?'checked':''; ?>>
        <input type="text" class="form-control item-content" value="<?= htmlspecialchars($it['content']); ?>">
        <button class="btn btn-sm btn-danger ms-2 delete-item">&times;</button>
      </li>
      <?php endforeach; endif; ?>
    </ul>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
function saveItem(li){
  const id=li.dataset.id;
  const content=li.querySelector('.item-content').value;
  const done=li.querySelector('.item-done').checked;
  const list=li.parentElement;
  const data={action:'update',id:id,content:content,is_done:done,category:list.dataset.category,day:list.dataset.day,week_start:'<?= $week_start; ?>'};
  fetch('todolist_save.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}).then(r=>r.json()).then(j=>{if(!id)li.dataset.id=j.id;});
}
function attach(li){
  li.querySelector('.item-content').addEventListener('input',()=>saveItem(li));
  li.querySelector('.item-done').addEventListener('change',()=>saveItem(li));
  li.querySelector('.delete-item').addEventListener('click',()=>{fetch('todolist_save.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',id:li.dataset.id})}).then(()=>li.remove());});
}
document.querySelectorAll('.todolist').forEach(list=>{
  Sortable.create(list,{animation:150,onEnd:function(){const order=Array.from(list.children).map((li,i)=>({id:li.dataset.id,position:i}));fetch('todolist_save.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'order',order:order})});}});
  list.querySelectorAll('li').forEach(attach);
});
document.querySelectorAll('.add-item').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const list=document.querySelector(`.todolist[data-category='${btn.dataset.category}'][data-day='${btn.dataset.day}']`);
    const li=document.createElement('li');
    li.className='list-group-item d-flex align-items-center';
    li.innerHTML=`<input type="checkbox" class="form-check-input me-2 item-done"><input type="text" class="form-control item-content"><button class="btn btn-sm btn-danger ms-2 delete-item">&times;</button>`;
    list.appendChild(li);
    attach(li);
    saveItem(li);
  });
});

function printTodoList(){
  const lang=document.documentElement.lang||'en';
  let html='<html><head><title>'+document.title+'</title><style>body{font-family:sans-serif;padding:10mm;}h3{margin-top:10mm;}ul{list-style:none;padding-left:0;}li{margin:4px 0;}li.done{text-decoration:line-through;color:#888;}</style></head><body>';
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
