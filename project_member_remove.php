<?php
include_once 'auth.php';

$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$isAjax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (strpos($acceptHeader, 'application/json') !== false)
);

$log_id = $_GET['log_id'] ?? ($_POST['log_id'] ?? null);
$project_id = $_GET['project_id'] ?? ($_POST['project_id'] ?? null);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $exit_time = $_POST['exit_time'] ?? null;
    if(!$log_id || !$exit_time){
        if ($isAjax) {
            header('Content-Type: application/json', true, 422);
            echo json_encode(['status' => 'error', 'error_key' => $exit_time ? 'project_members.error_remove' : 'project_members.error_exit_required'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        header('Location: projects.php');
        exit();
    }

    $stmt = $pdo->prepare('UPDATE project_member_log SET exit_time=? WHERE id=?');
    $stmt->execute([$exit_time,$log_id]);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if($project_id){
        header('Location: project_members.php?id='.$project_id);
    } else {
        header('Location: projects.php');
    }
    exit();
}

if ($isAjax) {
    header('Content-Type: application/json', true, 405);
    echo json_encode(['status' => 'error', 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit();
}

include 'header.php';
?>
<h2 data-i18n="project_members.remove_title">Remove Member</h2>
<form method="post">
  <input type="hidden" name="log_id" value="<?= htmlspecialchars($log_id ?? ''); ?>">
  <input type="hidden" name="project_id" value="<?= htmlspecialchars($project_id ?? ''); ?>">
  <div class="mb-3">
    <label class="form-label" data-i18n="project_members.remove_date">Exit Date</label>
    <input type="date" name="exit_time" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-danger" data-i18n="project_members.remove_confirm">Remove</button>
  <a href="<?php echo $project_id ? 'project_members.php?id='.urlencode($project_id) : 'projects.php'; ?>" class="btn btn-secondary" data-i18n="project_members.remove_cancel">Cancel</a>
</form>
<?php include 'footer.php'; ?>
