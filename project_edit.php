<?php
include 'header.php';
$id = $_GET['id'] ?? null;
$project = ['title'=>'','description'=>'','bg_color'=>'#ffffff','begin_date'=>'','end_date'=>'','status'=>'todo'];
$error = '';
if($id){
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id=?');
    $stmt->execute([$id]);
    $project = $stmt->fetch();
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $title = $_POST['title'];
    $description = $_POST['description'];
    $bg_color = $_POST['bg_color'];
    $begin_date = $_POST['begin_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    if($begin_date && $end_date && strtotime($end_date) <= strtotime($begin_date)){
        $error = '结项时间必须晚于立项时间';
    } else {
        if($id){
            $stmt = $pdo->prepare('UPDATE projects SET title=?, description=?, bg_color=?, begin_date=?, end_date=?, status=? WHERE id=?');
            $stmt->execute([$title,$description,$bg_color,$begin_date,$end_date,$status,$id]);
        } else {
            $orderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order),-1)+1 FROM projects');
            $nextOrder = $orderStmt->fetchColumn();
            $stmt = $pdo->prepare('INSERT INTO projects(title,description,bg_color,begin_date,end_date,status,sort_order) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$title,$description,$bg_color,$begin_date,$end_date,$status,$nextOrder]);
        }
        header('Location: projects.php');
        exit();
    }
}
?>
<h2><?php echo $id? 'Edit':'Add'; ?> 横纵项目</h2>
<?php if($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="post">
  <div class="mb-3">
    <label class="form-label">项目标题</label>
    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($project['title']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">项目描述</label>
    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($project['description']); ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">背景颜色</label>
    <input type="color" name="bg_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($project['bg_color'] ?? '#ffffff'); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">立项时间</label>
    <input type="date" name="begin_date" class="form-control" value="<?php echo htmlspecialchars($project['begin_date']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">结项时间</label>
    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($project['end_date']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">状态</label>
    <select name="status" class="form-select">
      <?php
      $statuses = ['todo'=>'Todo','ongoing'=>'Ongoing','paused'=>'Paused','finished'=>'Finished'];
      foreach($statuses as $key=>$val){
          $sel = $project['status']==$key?'selected':'';
          echo "<option value='$key' $sel>$val</option>";
      }
      ?>
    </select>
  </div>
  <button type="submit" class="btn btn-primary">更新</button>
  <a href="projects.php" class="btn btn-secondary">取消</a>
</form>
<script>
const projForm = document.querySelector('form');
projForm.addEventListener('submit', function(e){
  const begin = projForm.querySelector('input[name="begin_date"]').value;
  const end = projForm.querySelector('input[name="end_date"]').value;
  if(begin && end && new Date(end) <= new Date(begin)){
    alert('结项时间必须晚于立项时间');
    e.preventDefault();
  }
});
</script>
<?php include 'footer.php'; ?>
