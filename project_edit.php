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
        $error = 'End date must be after begin date';
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
<h2 data-i18n="<?php echo $id? 'project_edit.title_edit':'project_edit.title_add'; ?>">
  <?php echo $id? 'Edit Project':'Add Project'; ?>
</h2>
<?php if($error): ?><div class="alert alert-danger" data-i18n="project_edit.error_range"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="post">
  <div class="mb-3">
    <label class="form-label" data-i18n="project_edit.label_title">Project Title</label>
    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($project['title']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="project_edit.label_description">Project Description</label>
    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($project['description']); ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="project_edit.label_bg">Background Color</label>
    <input type="color" name="bg_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($project['bg_color'] ?? '#ffffff'); ?>">
    <div class="mt-2">
      <?php
      $suggestedColors = ['#f1f9f7','#fffffa','#ffffff','#f1f5f9','#fbf4f6'];
      foreach ($suggestedColors as $color) {
          echo "<button type=\"button\" class=\"btn btn-sm border me-1\" style=\"background-color:$color;\" title=\"$color\" onclick=\"document.querySelector('input[name=bg_color]').value='$color'\"></button>";
      }
      ?>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="project_edit.label_begin">Begin Date</label>
    <input type="date" name="begin_date" class="form-control" value="<?php echo htmlspecialchars($project['begin_date']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="project_edit.label_end">End Date</label>
    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($project['end_date']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="project_edit.label_status">Status</label>
    <select name="status" class="form-select">
      <?php
      $statuses = [
        'todo'    => ['key'=>'projects.status.todo',    'text'=>'Todo'],
        'ongoing' => ['key'=>'projects.status.ongoing', 'text'=>'Ongoing'],
        'paused'  => ['key'=>'projects.status.paused',  'text'=>'Paused'],
        'finished'=> ['key'=>'projects.status.finished', 'text'=>'Finished']
      ];
      foreach($statuses as $key=>$info){
          $sel = $project['status']==$key?'selected':'';
          echo "<option value='$key' data-i18n='{$info['key']}' $sel>{$info['text']}</option>";
      }
      ?>
    </select>
  </div>
  <button type="submit" class="btn btn-primary" data-i18n="project_edit.save">Save</button>
  <a href="projects.php" class="btn btn-secondary" data-i18n="project_edit.cancel">Cancel</a>
</form>
<script>
const projForm = document.querySelector('form');
projForm.addEventListener('submit', function(e){
  const begin = projForm.querySelector('input[name="begin_date"]').value;
  const end = projForm.querySelector('input[name="end_date"]').value;
  if(begin && end && new Date(end) <= new Date(begin)){
    const lang = document.documentElement.lang || 'en';
    alert(translations[lang]['project_edit.error_range']);
    e.preventDefault();
  }
});
</script>
<?php include 'footer.php'; ?>
