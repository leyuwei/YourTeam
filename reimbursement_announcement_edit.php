<?php
include 'auth_manager.php';
$announcement = $pdo->query("SELECT content_en, content_zh FROM reimbursement_announcement WHERE id=1")
                    ->fetch(PDO::FETCH_ASSOC);
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $content_en = $_POST['content_en'];
    $content_zh = $_POST['content_zh'];
    $stmt = $pdo->prepare("UPDATE reimbursement_announcement SET content_en=?, content_zh=? WHERE id=1");
    $stmt->execute([$content_en, $content_zh]);
    header('Location: reimbursements.php');
    exit();
}
include 'header.php';
?>
<h2 data-i18n="reimburse.announcement.title">Reimbursement Announcement</h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.announcement.label_en">English Announcement</label>
    <textarea name="content_en" class="form-control" rows="4"><?= htmlspecialchars($announcement['content_en'] ?? ''); ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.announcement.label_zh">Chinese Announcement</label>
    <textarea name="content_zh" class="form-control" rows="4"><?= htmlspecialchars($announcement['content_zh'] ?? ''); ?></textarea>
    <div class="form-text" data-i18n="reimburse.announcement.note_html">You can use HTML tags for styling.</div>
  </div>
  <button type="submit" class="btn btn-primary" data-i18n="reimburse.batch.save">Save</button>
  <a href="reimbursements.php" class="btn btn-secondary" data-i18n="reimburse.batch.cancel">Cancel</a>
</form>
<?php include 'footer.php'; ?>
