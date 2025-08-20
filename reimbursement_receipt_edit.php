<?php
include 'auth.php';
include 'header.php';
$id = (int)($_GET['id'] ?? 0);
$batch_id = (int)($_GET['batch_id'] ?? 0);
$is_manager = ($_SESSION['role'] === 'manager');
$member_id = $_SESSION['member_id'] ?? 0;
$stmt = $pdo->prepare("SELECT r.*, b.price_limit, b.status AS batch_status FROM reimbursement_receipts r JOIN reimbursement_batches b ON r.batch_id=b.id WHERE r.id=?");
$stmt->execute([$id]);
$rec = $stmt->fetch();
if(!$rec || (!($is_manager) && $rec['member_id']!=$member_id)){
    echo '<div class="alert alert-danger">No permission</div>';
    include 'footer.php';
    exit;
}
if($rec['status']!='submitted' || $rec['batch_status']!='open'){
    echo '<div class="alert alert-warning">Cannot edit this receipt</div>';
    include 'footer.php';
    exit;
}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $category = $_POST['category'];
    $description = $_POST['description'] ?? null;
    $price = $_POST['price'] !== '' ? (float)$_POST['price'] : 0;
    if(!$is_manager && $rec['price_limit'] !== null && $price > $rec['price_limit']){
        $error='exceed';
    } else {
        $orig = $rec['original_filename'];
        $stored = $rec['stored_filename'];
        if(isset($_FILES['receipt']) && $_FILES['receipt']['error']===UPLOAD_ERR_OK){
            $orig = $_FILES['receipt']['name'];
            $ext = pathinfo($orig, PATHINFO_EXTENSION);
            $stored = uniqid().'.'.$ext;
            $dir = __DIR__.'/reimburse_uploads/'.$rec['batch_id'];
            if(!is_dir($dir)) mkdir($dir,0777,true);
            move_uploaded_file($_FILES['receipt']['tmp_name'],$dir.'/'.$stored);
            $old = __DIR__.'/reimburse_uploads/'.$rec['batch_id'].'/'.$rec['stored_filename'];
            if(is_file($old)) unlink($old);
        }
        $stmt = $pdo->prepare("UPDATE reimbursement_receipts SET original_filename=?, stored_filename=?, category=?, description=?, price=? WHERE id=?");
        $stmt->execute([$orig,$stored,$category,$description,$price,$id]);
        header('Location: reimbursement_batch.php?id='.$rec['batch_id']);
        exit;
    }
}
?>
<h2 data-i18n="reimburse.batch.edit">Edit Receipt</h2>
<form method="post" enctype="multipart/form-data">
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.file">Receipt File</label>
    <input type="file" name="receipt" class="form-control">
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
    <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($rec['description']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.price">Price</label>
    <input type="number" step="0.01" name="price" class="form-control" value="<?= htmlspecialchars($rec['price']); ?>" required>
  </div>
  <?php if($error=='exceed'): ?><div class="alert alert-danger" data-i18n="reimburse.batch.limit_exceed">Price exceeds limit</div><?php endif; ?>
  <button type="submit" class="btn btn-primary" data-i18n="reimburse.batch.save">Save</button>
  <a href="reimbursement_batch.php?id=<?= $rec['batch_id']; ?>" class="btn btn-secondary" data-i18n="reimburse.batch.cancel">Cancel</a>
</form>
<?php include 'footer.php'; ?>
