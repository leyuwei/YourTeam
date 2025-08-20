<?php
include 'auth.php';
$id = (int)($_GET['id'] ?? 0);
$is_manager = ($_SESSION['role'] === 'manager');
$member_id = $_SESSION['member_id'] ?? 0;
$stmt = $pdo->prepare("SELECT r.*, b.price_limit, b.status AS batch_status, b.title AS batch_title FROM reimbursement_receipts r JOIN reimbursement_batches b ON r.batch_id=b.id WHERE r.id=?");
$stmt->execute([$id]);
$rec = $stmt->fetch();
if(!$rec || (!$is_manager && $rec['member_id'] != $member_id)){
    include 'header.php';
    echo '<div class="alert alert-danger">No permission</div>';
    include 'footer.php';
    exit;
}
$can_edit = ($rec['status']=='refused') || ($rec['status']=='submitted' && $rec['batch_status']=='open');
if(!$can_edit){
    include 'header.php';
    echo '<div class="alert alert-warning">Cannot edit this receipt</div>';
    include 'footer.php';
    exit;
}
$open_batches = $pdo->query("SELECT id,title,price_limit FROM reimbursement_batches WHERE status='open' ORDER BY deadline ASC")->fetchAll();
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $target_batch_id = (int)($_POST['batch_id'] ?? $rec['batch_id']);
    $batchStmt = $pdo->prepare("SELECT title, price_limit FROM reimbursement_batches WHERE id=? AND status='open'");
    $batchStmt->execute([$target_batch_id]);
    $targetBatch = $batchStmt->fetch();
    if(!$targetBatch){
        $error='batch';
    } else {
        $category = $_POST['category'];
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'] !== '' ? (float)$_POST['price'] : 0;
        if($description===''){
            $error='desc';
        } else {
            $totalStmt = $pdo->prepare("SELECT COALESCE(SUM(price),0) FROM reimbursement_receipts WHERE batch_id=? AND member_id=? AND id<>? AND status<>'refused'");
            $totalStmt->execute([$target_batch_id,$rec['member_id'],$id]);
            $currentTotal = (float)$totalStmt->fetchColumn();
            if(!$is_manager && $targetBatch['price_limit'] !== null && $currentTotal + $price > $targetBatch['price_limit']){
                $error='exceed';
            } else {
                $memberInfo = $pdo->prepare("SELECT name,campus_id FROM members WHERE id=?");
                $memberInfo->execute([$rec['member_id']]);
                $mi = $memberInfo->fetch();
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM reimbursement_receipts WHERE batch_id=? AND member_id=? AND id<>? AND status<>'refused'");
                $countStmt->execute([$target_batch_id,$rec['member_id'],$id]);
                $index = $countStmt->fetchColumn()+1;
                $base = $mi['name'].'-'.$mi['campus_id'].'-'.$targetBatch['title'].'-'.$index;
                $orig = $rec['original_filename'];
                $stored = $rec['stored_filename'];
                if($rec['status']=='refused'){
                    if(!isset($_FILES['receipt']) || $_FILES['receipt']['error']!==UPLOAD_ERR_OK){
                        $error='file';
                    } else {
                        $orig = $_FILES['receipt']['name'];
                        $ext = pathinfo($orig, PATHINFO_EXTENSION);
                        $stored = $base.'.'.$ext;
                        $dir = __DIR__.'/reimburse_uploads/'.$target_batch_id;
                        if(!is_dir($dir)) mkdir($dir,0777,true);
                        move_uploaded_file($_FILES['receipt']['tmp_name'], $dir.'/'.$stored);
                        @unlink(__DIR__.'/reimburse_uploads/'.$rec['batch_id'].'/'.$rec['stored_filename']);
                    }
                } else {
                    $ext = pathinfo($stored, PATHINFO_EXTENSION);
                    $newname = $base.'.'.$ext;
                    if($target_batch_id != $rec['batch_id'] || $newname != $stored){
                        $src = __DIR__.'/reimburse_uploads/'.$rec['batch_id'].'/'.$stored;
                        $dir = __DIR__.'/reimburse_uploads/'.$target_batch_id;
                        if(!is_dir($dir)) mkdir($dir,0777,true);
                        rename($src, $dir.'/'.$newname);
                        $stored = $newname;
                    }
                }
                if(!$error){
                    $stmt = $pdo->prepare("UPDATE reimbursement_receipts SET batch_id=?, original_filename=?, stored_filename=?, category=?, description=?, price=?, status='submitted' WHERE id=?");
                    $stmt->execute([$target_batch_id,$orig,$stored,$category,$description,$price,$id]);
                    header('Location: reimbursement_batch.php?id='.$target_batch_id);
                    exit;
                }
            }
        }
    }
}
include 'header.php';
?>
<h2 data-i18n="reimburse.batch.edit">Edit Receipt</h2>
<form method="post" enctype="multipart/form-data">
  <?php if($rec['status']=='refused'): ?>
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.file">Receipt File</label>
    <input type="file" name="receipt" class="form-control" required>
  </div>
  <?php endif; ?>
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.batch">Batch</label>
    <select name="batch_id" class="form-select" required>
      <?php foreach($open_batches as $b): ?>
      <option value="<?= $b['id']; ?>" <?php if($b['id']==$rec['batch_id']) echo 'selected'; ?>><?= htmlspecialchars($b['title']); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.category">Category</label>
    <select name="category" class="form-select" required>
      <option value="office" data-i18n="reimburse.category.office" <?php if($rec['category']=='office') echo 'selected'; ?>>Office Stuff</option>
      <option value="electronic" data-i18n="reimburse.category.electronic" <?php if($rec['category']=='electronic') echo 'selected'; ?>>Electronic Gadget</option>
      <option value="membership" data-i18n="reimburse.category.membership" <?php if($rec['category']=='membership') echo 'selected'; ?>>Membership</option>
      <option value="book" data-i18n="reimburse.category.book" <?php if($rec['category']=='book') echo 'selected'; ?>>Book</option>
      <option value="trip" data-i18n="reimburse.category.trip" <?php if($rec['category']=='trip') echo 'selected'; ?>>Trip</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.description">Description</label>
    <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($rec['description']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.price">Price</label>
    <input type="number" step="0.01" name="price" class="form-control" value="<?= htmlspecialchars($rec['price']); ?>" required>
  </div>
  <?php if($error=='exceed'): ?><div class="alert alert-danger" data-i18n="reimburse.batch.limit_exceed">Price exceeds limit</div><?php endif; ?>
  <?php if($error=='desc'): ?><div class="alert alert-danger" data-i18n="reimburse.batch.description_required">Description required</div><?php endif; ?>
  <?php if($error=='file'): ?><div class="alert alert-danger" data-i18n="reimburse.batch.file_required">File required</div><?php endif; ?>
  <button type="submit" class="btn btn-primary" data-i18n="reimburse.batch.save">Save</button>
  <?php if($rec['status']=='refused'): ?>
  <a href="reimbursements.php" class="btn btn-secondary" data-i18n="reimburse.batch.cancel">Cancel</a>
  <?php else: ?>
  <a href="reimbursement_batch.php?id=<?= $rec['batch_id']; ?>" class="btn btn-secondary" data-i18n="reimburse.batch.cancel">Cancel</a>
  <?php endif; ?>
</form>
<?php include 'footer.php'; ?>
