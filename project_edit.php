<?php
include 'header.php';
$id = $_GET['id'] ?? null;
$project = ['title'=>'','description'=>'','begin_date'=>'','end_date'=>'','status'=>'todo'];
if($id){
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id=?');
    $stmt->execute([$id]);
    $project = $stmt->fetch();
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $title = $_POST['title'];
    $description = $_POST['description'];
    $begin_date = $_POST['begin_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    if($id){
        $stmt = $pdo->prepare('UPDATE projects SET title=?, description=?, begin_date=?, end_date=?, status=? WHERE id=?');
        $stmt->execute([$title,$description,$begin_date,$end_date,$status,$id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO projects(title,description,begin_date,end_date,status) VALUES (?,?,?,?,?)');
        $stmt->execute([$title,$description,$begin_date,$end_date,$status]);
    }
    header('Location: projects.php');
    exit();
}
?>
<h2><?php echo $id? 'Edit':'Add'; ?> Project</h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($project['title']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($project['description']); ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Begin Date</label>
    <input type="date" name="begin_date" class="form-control" value="<?php echo htmlspecialchars($project['begin_date']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">End Date</label>
    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($project['end_date']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Status</label>
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
  <button type="submit" class="btn btn-primary">Save</button>
  <a href="projects.php" class="btn btn-secondary">Cancel</a>
</form>
<?php include 'footer.php'; ?>
