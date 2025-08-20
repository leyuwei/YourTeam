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
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] !== '' ? (float)$_POST['price'] : 0;
    if($description===''){
        $error='desc';
    } else {
        $totalStmt = $pdo->prepare("SELECT COALESCE(SUM(price),0) FROM reimbursement_receipts WHERE batch_id=? AND member_id=? AND id<>?");
        $totalStmt->execute([$rec['batch_id'],$rec['member_id'],$id]);
        $currentTotal = (float)$totalStmt->fetchColumn();
        if(!$is_manager && $rec['price_limit'] !== null && $currentTotal + $price > $rec['price_limit']){
            $error='exceed';
        } else {
            $stmt = $pdo->prepare("UPDATE reimbursement_receipts SET category=?, description=?, price=? WHERE id=?");
            $stmt->execute([$category,$description,$price,$id]);
            header('Location: reimbursement_batch.php?id='.$rec['batch_id']);
            exit;
        }
    }
}
?>
<h2 data-i18n="reimburse.batch.edit">Edit Receipt</h2>
<form method="post">
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
  <button type="submit" class="btn btn-primary" data-i18n="reimburse.batch.save">Save</button>
  <a href="reimbursement_batch.php?id=<?= $rec['batch_id']; ?>" class="btn btn-secondary" data-i18n="reimburse.batch.cancel">Cancel</a>
</form>
<?php include 'footer.php'; ?>
