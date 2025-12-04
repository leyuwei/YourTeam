<?php
include 'auth.php';
include 'reimbursement_log.php';
$id = (int)($_GET['id'] ?? 0);
$is_manager = ($_SESSION['role'] === 'manager');
$member_id = $_SESSION['member_id'] ?? 0;
$stmt = $pdo->prepare("SELECT r.*, b.price_limit, b.status AS batch_status, b.title AS batch_title, b.allowed_types FROM reimbursement_receipts r JOIN reimbursement_batches b ON r.batch_id=b.id WHERE r.id=?");
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
$open_batches = $pdo->query("SELECT id,title,price_limit,allowed_types FROM reimbursement_batches WHERE status='open' ORDER BY deadline ASC")->fetchAll();
$currentAllowed = $rec['allowed_types'] ? explode(',', $rec['allowed_types']) : ['office','electronic','membership','book','trip'];
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $target_batch_id = (int)($_POST['batch_id'] ?? $rec['batch_id']);
    $batchStmt = $pdo->prepare("SELECT title, price_limit, allowed_types FROM reimbursement_batches WHERE id=? AND status='open'");
    $batchStmt->execute([$target_batch_id]);
    $targetBatch = $batchStmt->fetch();
    if(!$targetBatch){
        $error='batch';
    } else {
        $category = $_POST['category'];
        $allowedCats = $targetBatch['allowed_types'] ? explode(',', $targetBatch['allowed_types']) : ['office','electronic','membership','book','trip'];
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'] !== '' ? (float)$_POST['price'] : 0;
        if($description===''){
            $error='desc';
        } elseif(!in_array($category,$allowedCats)){
            $error='type';
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
                    if($is_manager){
                        $error='manager';
                    } elseif(!isset($_FILES['receipt']) || $_FILES['receipt']['error']!==UPLOAD_ERR_OK){
                        $error='file';
                    } else {
                    $orig = $_FILES['receipt']['name'];
                    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                    $tmpPath = $_FILES['receipt']['tmp_name'];
                    $orig_base = pathinfo($orig, PATHINFO_FILENAME);
                    $suffix = mt_rand(1000,9999) . '-' . time();
                    $orig = $orig_base . '-' . $mi['name'] . '-' . $suffix . '.' . $ext;
                    if($ext==='pdf'){
                        $keywords=$pdo->query("SELECT keyword FROM reimbursement_prohibited_keywords")->fetchAll(PDO::FETCH_COLUMN);
                        $content=@shell_exec('pdftotext '.escapeshellarg($tmpPath).' -');
                        if(!$content){ $content=@file_get_contents($tmpPath); }
                        foreach($keywords as $kw){
                            if($kw!=='' && stripos($content,$kw)!==false){
                                $error='prohibited';
                                break;
                            }
                        }
                    }
                    if(!$error){
                        $stored = $base . '-' . $suffix . '.' . $ext;
                        $dir = __DIR__.'/reimburse_uploads/'.$target_batch_id;
                        if(!is_dir($dir)) mkdir($dir,0777,true);
                        move_uploaded_file($tmpPath, $dir.'/'.$stored);
                        @unlink(__DIR__.'/reimburse_uploads/'.$rec['batch_id'].'/'.$rec['stored_filename']);
                    }
                }
            } else {
                $ext = pathinfo($stored, PATHINFO_EXTENSION);
                    $storedBase = pathinfo($stored, PATHINFO_FILENAME);
                    if($target_batch_id != $rec['batch_id'] || strpos($storedBase, $base . '-') !== 0){
                        $newname = $base . '-' . mt_rand(1000,9999) . '-' . time() . '.' . $ext;
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
                    $changes=[];
                    if($rec['batch_id']!=$target_batch_id) $changes[]='batch';
                    if($rec['category']!=$category) $changes[]='category';
                    if($rec['description']!=$description) $changes[]='description';
                    if($rec['price']!=$price) $changes[]='price';
                    $msg='Receipt '.$id.' updated'.($changes?': '.implode(', ',$changes):'');
                    add_batch_log($pdo,$target_batch_id,$_SESSION['username'],$msg);
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
  <?php if($rec['status']=='refused' && !$is_manager): ?>
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
      <?php foreach($currentAllowed as $t): ?>
      <option value="<?= $t; ?>" data-i18n="reimburse.category.<?= $t; ?>" <?php if($rec['category']==$t) echo 'selected'; ?>><?= $t; ?></option>
      <?php endforeach; ?>
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
  <?php if($error=='manager'): ?><div class="alert alert-danger" data-i18n="reimburse.batch.manager_no_upload">Managers cannot upload receipts</div><?php endif; ?>
  <?php if($error=='type'): ?><div class="alert alert-danger" data-i18n="reimburse.batch.type_not_allowed">Type not allowed</div><?php endif; ?>
  <?php if($error=='prohibited'): ?><div class="alert alert-danger" data-i18n="reimburse.batch.prohibited">Receipt contains prohibited content</div><?php endif; ?>
  <button type="submit" class="btn btn-primary" data-i18n="reimburse.batch.save">Save</button>
  <?php if($rec['status']=='refused'): ?>
  <a href="reimbursements.php" class="btn btn-secondary" data-i18n="reimburse.batch.cancel">Cancel</a>
  <?php else: ?>
  <a href="reimbursement_batch.php?id=<?= $rec['batch_id']; ?>" class="btn btn-secondary" data-i18n="reimburse.batch.cancel">Cancel</a>
  <?php endif; ?>
</form>
<script>
const batchTypes = <?php echo json_encode(array_column($open_batches,'allowed_types','id')); ?>;
const allCats=['office','electronic','membership','book','trip'];
const catSelect=document.querySelector('select[name="category"]');
const batchSelect=document.querySelector('select[name="batch_id"]');
function updateCats(){
  let allowed=batchTypes[batchSelect.value];
  allowed=allowed?allowed.split(','):allCats;
  catSelect.innerHTML='';
  allowed.forEach(t=>{
    const opt=document.createElement('option');
    opt.value=t;
    opt.setAttribute('data-i18n','reimburse.category.'+t);
    opt.textContent=translations[document.documentElement.lang||'zh']['reimburse.category.'+t];
    catSelect.appendChild(opt);
  });
  if(allowed.includes('<?php echo $rec['category']; ?>')){
    catSelect.value='<?php echo $rec['category']; ?>';
  }
}
batchSelect.addEventListener('change',updateCats);
updateCats();
</script>
<?php include 'footer.php'; ?>
