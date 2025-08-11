<?php
include 'header.php';
$log_id = $_GET['log_id'] ?? null;
$project_id = $_GET['project_id'] ?? null;
if(!$log_id){
    header('Location: projects.php');
    exit();
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $exit_time = $_POST['exit_time'];
    $stmt = $pdo->prepare('UPDATE project_member_log SET exit_time=? WHERE id=?');
    $stmt->execute([$exit_time,$log_id]);
    header('Location: project_members.php?id='.$project_id);
    exit();
}
?>
<h2>Remove Member</h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Exit Time</label>
    <input type="datetime-local" name="exit_time" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-danger">Remove</button>
  <a href="project_members.php?id=<?= $project_id; ?>" class="btn btn-secondary">Cancel</a>
</form>
<?php include 'footer.php'; ?>
