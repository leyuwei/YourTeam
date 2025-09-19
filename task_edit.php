<?php
include 'auth_manager.php';

$id = $_GET['id'] ?? null;
$task = ['title'=>'','description'=>'','start_date'=>'','status'=>'active'];
if($id){
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id=?');
    $stmt->execute([$id]);
    $task = $stmt->fetch();
    if(!$task){
        header('Location: tasks.php');
        exit();
    }
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $status = $_POST['status'];
    $redirectUrl = 'tasks.php';
    if($id){
        $stmt = $pdo->prepare('UPDATE tasks SET title=?, description=?, start_date=?, status=? WHERE id=?');
        $stmt->execute([$title,$description,$start_date,$status,$id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO tasks(title,description,start_date,status) VALUES (?,?,?,?)');
        $stmt->execute([$title,$description,$start_date,$status]);
        $newTaskId = $pdo->lastInsertId();
        if($newTaskId){
            $redirectUrl = 'task_affairs.php?id=' . $newTaskId;
        }
    }
    header('Location: ' . $redirectUrl);
    exit();
}

include 'header.php';
?>
<h2 data-i18n="<?php echo $id? 'task_edit.title_edit':'task_edit.title_add'; ?>">
  <?php echo $id? 'Edit Task':'Add Task'; ?>
</h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label" data-i18n="tasks.table_title">Title</label>
    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($task['title']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="task_edit.label_description">Description</label>
    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($task['description']); ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="task_edit.label_start">Start Date</label>
    <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($task['start_date']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="task_edit.label_status">Status</label>
    <select name="status" class="form-select">
      <?php $statuses=['active'=>['key'=>'tasks.status.active','text'=>'Active'],
                       'paused'=>['key'=>'tasks.status.paused','text'=>'Paused'],
                       'finished'=>['key'=>'tasks.status.finished','text'=>'Finished']];
      foreach($statuses as $k=>$v){
        $sel = $task['status']==$k?'selected':'';
        echo "<option value='$k' data-i18n='{$v['key']}' $sel>{$v['text']}</option>";
      }?>
    </select>
  </div>
  <button type="submit" class="btn btn-primary" data-i18n="task_edit.save">Save</button>
  <a href="tasks.php" class="btn btn-secondary" data-i18n="task_edit.cancel">Cancel</a>
</form>
<?php include 'footer.php'; ?>
