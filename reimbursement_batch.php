<?php
include 'auth.php';
include 'header.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, m.name AS in_charge_name FROM reimbursement_batches b LEFT JOIN members m ON b.in_charge_member_id=m.id WHERE b.id=?");
$stmt->execute([$id]);
$batch = $stmt->fetch();
if(!$batch){
    echo '<div class="alert alert-danger">Batch not found</div>';
    include 'footer.php';
    exit;
}
$is_manager = ($_SESSION['role'] === 'manager');
$member_id = $_SESSION['member_id'] ?? null;
$deadline_passed = (strtotime($batch['deadline']) < strtotime(date('Y-m-d')));
$batch_locked = ($batch['status'] !== 'open');
$error = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['lock']) && ($is_manager || $batch['in_charge_member_id']==$member_id)){
        $pdo->prepare("UPDATE reimbursement_batches SET status='locked' WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE reimbursement_receipts SET status='locked' WHERE batch_id=?")->execute([$id]);
        $batch['status']='locked';
        $batch_locked = true;
    } elseif(isset($_POST['complete']) && ($is_manager || $batch['in_charge_member_id']==$member_id)){
        $pdo->prepare("UPDATE reimbursement_batches SET status='completed' WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE reimbursement_receipts SET status='complete' WHERE batch_id=?")->execute([$id]);
        $batch['status']='completed';
        $batch_locked = true;
    } elseif(isset($_FILES['receipt']) && $_FILES['receipt']['error']===UPLOAD_ERR_OK && !$batch_locked && (!$deadline_passed || $is_manager)){
        $price = $_POST['price'] !== '' ? (float)$_POST['price'] : 0;
        if(!$is_manager && $batch['price_limit'] !== null && $price > $batch['price_limit']){
            $error = 'exceed';
        } else {
            $category = $_POST['category'];
            $description = $_POST['description'] ?? null;
            $orig = $_FILES['receipt']['name'];
            $ext = pathinfo($orig, PATHINFO_EXTENSION);
            $newname = uniqid().'.'.$ext;
            $dir = __DIR__.'/reimburse_uploads/'.$id;
            if(!is_dir($dir)) mkdir($dir,0777,true);
            move_uploaded_file($_FILES['receipt']['tmp_name'], $dir.'/'.$newname);
            $stmt = $pdo->prepare("INSERT INTO reimbursement_receipts (batch_id, member_id, original_filename, stored_filename, category, description, price) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$id, $member_id, $orig, $newname, $category, $description, $price]);
        }
    }
}

if($is_manager || $batch['in_charge_member_id']==$member_id){
    $stmt = $pdo->prepare("SELECT r.*, m.name FROM reimbursement_receipts r JOIN members m ON r.member_id=m.id WHERE r.batch_id=? ORDER BY r.id DESC");
    $stmt->execute([$id]);
    $sumMembers = $pdo->prepare("SELECT m.campus_id, m.name, SUM(r.price) AS total FROM reimbursement_receipts r JOIN members m ON r.member_id=m.id WHERE r.batch_id=? GROUP BY r.member_id");
    $sumMembers->execute([$id]);
    $member_totals = $sumMembers->fetchAll();
    $sumCats = $pdo->prepare("SELECT category, SUM(price) AS total FROM reimbursement_receipts WHERE batch_id=? GROUP BY category");
    $sumCats->execute([$id]);
    $category_totals = $sumCats->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT r.*, m.name FROM reimbursement_receipts r JOIN members m ON r.member_id=m.id WHERE r.batch_id=? AND r.member_id=? ORDER BY r.id DESC");
    $stmt->execute([$id,$member_id]);
}
$receipts = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between mb-3">
  <h2><?= htmlspecialchars($batch['title']); ?></h2>
  <?php if($is_manager || $batch['in_charge_member_id']==$member_id): ?>
  <a class="btn btn-info" href="reimbursement_download.php?id=<?= $batch['id']; ?>" data-i18n="reimburse.action_download">Download</a>
  <?php endif; ?>
</div>
<p><strong data-i18n="reimburse.batch.incharge">In Charge:</strong> <?= htmlspecialchars($batch['in_charge_name']); ?> &nbsp; <strong data-i18n="reimburse.batch.deadline">Deadline:</strong> <?= htmlspecialchars($batch['deadline']); ?> &nbsp; <strong data-i18n="reimburse.batch.limit">Limit:</strong> <?= htmlspecialchars($batch['price_limit']); ?> &nbsp; <strong data-i18n="reimburse.batch.status">Status:</strong> <?= htmlspecialchars($batch['status']); ?></p>
<?php if(!$batch_locked && (!$deadline_passed || $is_manager)): ?>
<form method="post" enctype="multipart/form-data" class="mb-4">
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.file">Receipt File</label>
    <input type="file" name="receipt" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.category">Category</label>
    <select name="category" class="form-select" required>
      <option value="office" data-i18n="reimburse.category.office">Office Stuff</option>
      <option value="electronic" data-i18n="reimburse.category.electronic">Electronic Gadget</option>
      <option value="membership" data-i18n="reimburse.category.membership">Membership</option>
      <option value="book" data-i18n="reimburse.category.book">Book</option>
      <option value="trip" data-i18n="reimburse.category.trip">Trip</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.description">Description</label>
    <input type="text" name="description" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="reimburse.batch.price">Price</label>
    <input type="number" step="0.01" name="price" class="form-control" required>
  </div>
  <?php if($error=='exceed'): ?><div class="alert alert-danger" data-i18n="reimburse.batch.limit_exceed">Price exceeds limit</div><?php endif; ?>
  <button type="submit" class="btn btn-primary" data-i18n="reimburse.batch.upload">Upload</button>
</form>
<?php else: ?>
<div class="alert alert-warning" data-i18n="reimburse.batch.deadline_passed">Deadline passed or batch locked</div>
<?php endif; ?>
<table class="table table-bordered">
<tr><th data-i18n="reimburse.batch.receipt">Receipt</th><th data-i18n="reimburse.batch.category">Category</th><th data-i18n="reimburse.batch.description">Description</th><th data-i18n="reimburse.batch.price">Price</th><th data-i18n="reimburse.batch.status">Status</th><th data-i18n="reimburse.batch.uploader">Uploader</th><th data-i18n="reimburse.batch.actions">Actions</th></tr>
<?php foreach($receipts as $r): ?>
<tr>
  <td><a href="<?='reimburse_uploads/'.$id.'/'.urlencode($r['stored_filename']);?>" target="_blank"><?= htmlspecialchars($r['original_filename']); ?></a></td>
  <td><?= htmlspecialchars($r['category']); ?></td>
  <td><?= htmlspecialchars($r['description']); ?></td>
  <td><?= htmlspecialchars($r['price']); ?></td>
  <td><?= htmlspecialchars($r['status']); ?></td>
  <td><?= htmlspecialchars($r['name']); ?></td>
  <td>
    <?php if($is_manager || ($r['member_id']==$member_id && $r['status']=='submitted' && !$batch_locked)): ?>
    <a class="btn btn-sm btn-secondary" href="reimbursement_receipt_edit.php?id=<?= $r['id']; ?>&batch_id=<?= $id; ?>" data-i18n="reimburse.batch.edit">Edit</a>
    <?php endif; ?>
    <?php if($is_manager || ($r['member_id']==$member_id && $r['status']=='submitted' && !$batch_locked)): ?>
    <a class="btn btn-sm btn-danger" href="reimbursement_receipt_delete.php?id=<?= $r['id']; ?>&batch_id=<?= $id; ?>" data-i18n="reimburse.batch.delete" onclick="return doubleConfirm(translations[document.documentElement.lang||'en']['reimburse.batch.confirm_delete']);">Delete</a>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php if($is_manager || $batch['in_charge_member_id']==$member_id): ?>
<h4 data-i18n="reimburse.batch.total_member">Total by Member</h4>
<table class="table table-bordered">
<tr><th data-i18n="reimburse.batch.campus_id">Campus ID</th><th data-i18n="reimburse.batch.uploader">Member</th><th data-i18n="reimburse.batch.total">Total</th></tr>
<?php foreach($member_totals as $mt): ?>
<tr><td><?= htmlspecialchars($mt['campus_id']); ?></td><td><?= htmlspecialchars($mt['name']); ?></td><td><?= htmlspecialchars($mt['total']); ?></td></tr>
<?php endforeach; ?>
</table>
<h4 data-i18n="reimburse.batch.total_category">Total by Category</h4>
<table class="table table-bordered">
<tr><th data-i18n="reimburse.batch.category">Category</th><th data-i18n="reimburse.batch.total">Total</th></tr>
<?php foreach($category_totals as $ct): ?>
<tr><td><?= htmlspecialchars($ct['category']); ?></td><td><?= htmlspecialchars($ct['total']); ?></td></tr>
<?php endforeach; ?>
</table>
<form method="post" class="mt-3">
  <?php if($batch['status']=='open'): ?>
  <button type="submit" name="lock" value="1" class="btn btn-warning" data-i18n="reimburse.batch.lock">Lock</button>
  <?php elseif($batch['status']=='locked'): ?>
  <button type="submit" name="complete" value="1" class="btn btn-success" data-i18n="reimburse.batch.complete">Complete</button>
  <?php endif; ?>
</form>
<?php endif; ?>
<?php include 'footer.php'; ?>
