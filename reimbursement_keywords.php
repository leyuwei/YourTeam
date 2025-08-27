<?php
include 'auth_manager.php';
include 'header.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $kw=trim($_POST['keyword']??'');
    if($kw!==''){
        $stmt=$pdo->prepare("INSERT IGNORE INTO reimbursement_prohibited_keywords (keyword) VALUES (?)");
        $stmt->execute([$kw]);
    }
}
if(isset($_GET['del'])){
    $id=(int)$_GET['del'];
    $pdo->prepare("DELETE FROM reimbursement_prohibited_keywords WHERE id=?")->execute([$id]);
    header('Location: reimbursement_keywords.php');
    exit;
}
$keywords=$pdo->query("SELECT * FROM reimbursement_prohibited_keywords ORDER BY keyword")->fetchAll();
?>
<h2 data-i18n="reimburse.keywords.manage">Prohibited Keywords</h2>
<form method="post" class="mb-3">
  <div class="input-group">
    <input type="text" name="keyword" class="form-control" required>
    <button class="btn btn-primary" data-i18n="reimburse.keywords.add">Add</button>
  </div>
</form>
<table class="table table-bordered">
<tr><th data-i18n="reimburse.keywords.word">Keyword</th><th data-i18n="reimburse.batch.actions">Actions</th></tr>
<?php foreach($keywords as $k): ?>
<tr>
  <td><?= htmlspecialchars($k['keyword']); ?></td>
  <td><a class="btn btn-sm btn-danger" href="?del=<?= $k['id']; ?>" onclick="return doubleConfirm(translations[document.documentElement.lang||'zh']['reimburse.batch.confirm_delete']);" data-i18n="reimburse.batch.delete">Delete</a></td>
</tr>
<?php endforeach; ?>
</table>
<?php include 'footer.php'; ?>

