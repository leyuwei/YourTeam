<?php
include 'auth_manager.php';
include 'header.php';
$id = $_GET['id'] ?? null;
$task = ['title'=>'','description'=>'','start_date'=>'','status'=>'active'];
if($id){
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id=?');
    $stmt->execute([$id]);
    $task = $stmt->fetch();
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $status = $_POST['status'];
    if($id){
        $stmt = $pdo->prepare('UPDATE tasks SET title=?, description=?, start_date=?, status=? WHERE id=?');
        $stmt->execute([$title,$description,$start_date,$status,$id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO tasks(title,description,start_date,status) VALUES (?,?,?,?)');
        $stmt->execute([$title,$description,$start_date,$status]);
    }
    header('Location: tasks.php');
    exit();
}
?>
<h2><?php echo $id? 'Edit':'Add'; ?> 任务指派</h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label">任务标题</label>
    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($task['title']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">任务描述</label>
    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($task['description']); ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">起始时间</label>
    <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($task['start_date']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">状态</label>
    <select name="status" class="form-select">
      <?php $statuses=['active'=>'Active','paused'=>'Paused','finished'=>'Finished'];
      foreach($statuses as $k=>$v){
        $sel = $task['status']==$k?'selected':'';
        echo "<option value='$k' $sel>$v</option>";
      }?>
    </select>
  </div>
  <button type="submit" class="btn btn-primary">更新</button>
  <a href="tasks.php" class="btn btn-secondary">取消</a>
</form>
<?php include 'footer.php'; ?>
