<?php
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
$wantsJson = str_contains($acceptHeader, 'application/json') || $requestedWith === 'xmlhttprequest';
if($wantsJson){
    include 'auth.php';
} else {
    include 'header.php';
}
$log_id = $_GET['log_id'] ?? $_POST['log_id'] ?? null;
$project_id = $_GET['project_id'] ?? $_POST['project_id'] ?? null;
if(!$log_id){
    if($wantsJson){
        header('Content-Type: application/json');
        echo json_encode(['status'=>'error','message'=>'project_members.invalid_request']);
        exit();
    }
    header('Location: projects.php');
    exit();
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $exit_time = $_POST['exit_time'] ?? null;
    if(!$exit_time){
        if($wantsJson){
            header('Content-Type: application/json');
            echo json_encode(['status'=>'error','message'=>'project_members.invalid_request']);
            exit();
        }
        header('Location: project_members.php?id='.$project_id);
        exit();
    }
    $stmt = $pdo->prepare('UPDATE project_member_log SET exit_time=? WHERE id=?');
    $stmt->execute([$exit_time,$log_id]);
    if($wantsJson){
        header('Content-Type: application/json');
        echo json_encode(['status'=>'ok','log_id'=>$log_id,'exit_time'=>$exit_time]);
        exit();
    }
    header('Location: project_members.php?id='.$project_id);
    exit();
}
if($wantsJson){
    header('Content-Type: application/json');
    echo json_encode(['status'=>'error','message'=>'project_members.invalid_request']);
    exit();
}
?>
<h2 data-i18n="project_members.remove_confirm_title">Remove Member</h2>
<form method="post">
  <input type="hidden" name="log_id" value="<?= htmlspecialchars($log_id); ?>">
  <input type="hidden" name="project_id" value="<?= htmlspecialchars($project_id); ?>">
  <div class="mb-3">
    <label class="form-label" data-i18n="project_members.label_exit">Exit Date</label>
    <input type="date" name="exit_time" class="form-control" value="<?= htmlspecialchars(date('Y-m-d')); ?>" required>
  </div>
  <button type="submit" class="btn btn-danger" data-i18n="project_members.remove">Remove</button>
  <a href="project_members.php?id=<?= $project_id; ?>" class="btn btn-secondary" data-i18n="project_edit.cancel">Cancel</a>
</form>
<?php include 'footer.php'; ?>
