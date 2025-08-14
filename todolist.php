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
$days = ['mon'=>'周一','tue'=>'周二','wed'=>'周三','thu'=>'周四','fri'=>'周五','sat'=>'周六','sun'=>'周日'];
?>
<h2>待办事项</h2>
<form method="get" class="mb-3">
  <input type="week" name="week" value="<?= htmlspecialchars($week_param); ?>">
  <button type="submit" class="btn btn-secondary btn-sm">切换周</button>
  <a class="btn btn-success btn-sm" href="todolist_export.php?week=<?= urlencode($week_param); ?>">导出</a>
  <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">打印</button>
</form>
<div class="row">
  <div class="col-md-6">
    <h3><b>工作</b></h3>
    <?php foreach($days as $k=>$label): ?>
    <h5><?= $label; ?> <button type="button" class="btn btn-sm btn-outline-success add-item" data-category="work" data-day="<?= $k; ?>">+</button></h5>
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
    <h3><b>私人</b></h3>
    <?php foreach($days as $k=>$label): ?>
    <h5><?= $label; ?> <button type="button" class="btn btn-sm btn-outline-success add-item" data-category="personal" data-day="<?= $k; ?>">+</button></h5>
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
    <h3><b>长期</b> <button type="button" class="btn btn-sm btn-outline-success add-item" data-category="longterm" data-day="">+</button></h3>
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
</script>
<?php include 'footer.php'; ?>
