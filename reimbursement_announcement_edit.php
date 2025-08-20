<?php
include 'auth_manager.php';
$announcement = $pdo->query("SELECT content FROM reimbursement_announcement WHERE id=1")->fetchColumn();
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $content = $_POST['content'];
    $stmt = $pdo->prepare("UPDATE reimbursement_announcement SET content=? WHERE id=1");
    $stmt->execute([$content]);
    header('Location: reimbursements.php');
    exit();
}
include 'header.php';
?>
<h2>Reimbursement Announcement</h2>
<form method="post">
  <div class="mb-3">
    <textarea name="content" class="form-control" rows="6"><?= htmlspecialchars($announcement); ?></textarea>
    <div class="form-text">You can use HTML tags for styling.</div>
  </div>
  <button type="submit" class="btn btn-primary">Save</button>
  <a href="reimbursements.php" class="btn btn-secondary">Cancel</a>
</form>
<?php include 'footer.php'; ?>
