<?php
include 'auth.php';
include 'header.php';
$is_manager = ($_SESSION['role'] === 'manager');
$member_id = $_SESSION['member_id'] ?? null;

if($is_manager && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = $_POST['id'] ?? '';
    $title = trim($_POST['title']);
    $incharge = $_POST['in_charge'] ?: null;
    $deadline = $_POST['deadline'];
    $limit = $_POST['price_limit'] !== '' ? $_POST['price_limit'] : null;
    if($id){
        $stmt = $pdo->prepare("UPDATE reimbursement_batches SET title=?, in_charge_member_id=?, deadline=?, price_limit=? WHERE id=?");
        $stmt->execute([$title, $incharge, $deadline, $limit, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO reimbursement_batches (title, in_charge_member_id, deadline, price_limit) VALUES (?,?,?,?)");
        $stmt->execute([$title, $incharge, $deadline, $limit]);
    }
}

$batches = $pdo->query("SELECT b.*, m.name AS in_charge_name, (SELECT COUNT(*) FROM reimbursement_receipts r WHERE r.batch_id=b.id) AS receipt_count FROM reimbursement_batches b LEFT JOIN members m ON b.in_charge_member_id=m.id ORDER BY (b.status='completed'), b.deadline ASC")->fetchAll();
$members = $pdo->query("SELECT id, name FROM members ORDER BY name")->fetchAll();
?>
<div class="d-flex justify-content-between mb-3">
  <h2 data-i18n="reimburse.title">Reimbursement Batches</h2>
  <div>
    <?php if($is_manager): ?>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#batchModal" data-i18n="reimburse.add_batch">Add Batch</button>
    <?php endif; ?>
  </div>
</div>
<table class="table table-bordered">
<tr><th data-i18n="reimburse.table_title">Title</th><th data-i18n="reimburse.table_deadline">Deadline</th><th data-i18n="reimburse.table_incharge">In Charge</th><th data-i18n="reimburse.batch.status">Status</th><th data-i18n="reimburse.batch.limit">Limit</th><?php if(!$is_manager) echo '<th data-i18n="reimburse.table_myreceipts">My Receipts</th>'; ?><th data-i18n="reimburse.table_actions">Actions</th></tr>
<?php foreach($batches as $b): ?>
<tr>
  <td><?= htmlspecialchars($b['title']); ?></td>
  <td><?= htmlspecialchars($b['deadline']); ?></td>
  <td><?= htmlspecialchars($b['in_charge_name']); ?></td>
  <td><span data-i18n="reimburse.status.<?= $b['status']; ?>"><?= htmlspecialchars($b['status']); ?></span></td>
  <td><?= htmlspecialchars($b['price_limit']); ?></td>
  <?php if(!$is_manager): ?>
  <td>
    <?php
      $stmt = $pdo->prepare("SELECT * FROM reimbursement_receipts WHERE batch_id=? AND member_id=? AND status<>'refused' ORDER BY id DESC");
      $stmt->execute([$b['id'],$member_id]);
      $urs = $stmt->fetchAll();
      if($urs){
        echo '<ul class="list-group list-group-flush mb-0">';
        foreach($urs as $r){
          echo '<li class="list-group-item px-2 py-1"><a href="reimburse_uploads/'.$b['id'].'/'.urlencode($r['stored_filename']).'" target="_blank">'.htmlspecialchars($r['stored_filename']).'</a><br><small>'.htmlspecialchars($r['description']).' - <span data-i18n="reimburse.category.'.$r['category'].'">'.htmlspecialchars($r['category']).'</span> - '.htmlspecialchars($r['price']).'</small></li>';
        }
        echo '</ul>';
      } else {
        echo '<span data-i18n="reimburse.batch.none">None</span>';
      }
    ?>
  </td>
  <?php endif; ?>
  <td>
    <a class="btn btn-sm btn-primary" href="reimbursement_batch.php?id=<?= $b['id']; ?>" data-i18n="reimburse.action_details">Details</a>
    <?php if($is_manager || $b['in_charge_member_id']==$member_id): ?>
    <a class="btn btn-sm btn-info" href="reimbursement_download.php?id=<?= $b['id']; ?>" data-i18n="reimburse.action_download">Download</a>
    <?php endif; ?>
    <?php if($is_manager): ?>
    <button class="btn btn-sm btn-warning edit-batch" data-id="<?= $b['id']; ?>" data-title="<?= htmlspecialchars($b['title'],ENT_QUOTES); ?>" data-incharge="<?= $b['in_charge_member_id']; ?>" data-deadline="<?= $b['deadline']; ?>" data-limit="<?= $b['price_limit']; ?>" data-i18n="reimburse.action_edit">Edit</button>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php if(!$is_manager): ?>
<?php
  $stmt = $pdo->prepare("SELECT r.*, b.title AS batch_title FROM reimbursement_receipts r JOIN reimbursement_batches b ON r.batch_id=b.id WHERE r.member_id=? AND r.status='refused' ORDER BY r.id DESC");
  $stmt->execute([$member_id]);
  $receipts = $stmt->fetchAll();
?>
<h3 data-i18n="reimburse.refused.title">Refused Receipts</h3>
<?php if($receipts): ?>
<table class="table table-bordered">
<tr><th data-i18n="reimburse.batch.receipt">Receipt</th><th data-i18n="reimburse.batch.category">Category</th><th data-i18n="reimburse.batch.description">Description</th><th data-i18n="reimburse.batch.price">Price</th><th data-i18n="reimburse.refused.original_batch">Original Batch</th><th data-i18n="reimburse.batch.actions">Actions</th></tr>
<?php foreach($receipts as $r): ?>
<tr>
  <td><a href="<?= 'reimburse_uploads/'.$r['batch_id'].'/'.urlencode($r['stored_filename']); ?>" target="_blank"><?= htmlspecialchars($r['stored_filename']); ?></a></td>
  <td><span data-i18n="reimburse.category.<?= $r['category']; ?>"><?= htmlspecialchars($r['category']); ?></span></td>
  <td><?= htmlspecialchars($r['description']); ?></td>
  <td><?= htmlspecialchars($r['price']); ?></td>
  <td><?= htmlspecialchars($r['batch_title']); ?></td>
  <td><a class="btn btn-sm btn-secondary" href="reimbursement_receipt_edit.php?id=<?= $r['id']; ?>" data-i18n="reimburse.batch.edit">Edit</a></td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<div class="alert alert-info" data-i18n="reimburse.batch.none">None</div>
<?php endif; ?>
<?php endif; ?>
<?php if($is_manager): ?>
<div class="modal fade" id="batchModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post">
      <div class="modal-header">
        <h5 class="modal-title" id="batchModalLabel" data-i18n="reimburse.add_batch">Add Batch</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="batch-id">
        <div class="mb-3">
          <label class="form-label" data-i18n="reimburse.batch.title">Title</label>
          <input type="text" name="title" class="form-control" id="batch-title" required>
        </div>
        <div class="mb-3">
          <label class="form-label" data-i18n="reimburse.batch.incharge">In Charge</label>
          <select name="in_charge" class="form-select" id="batch-incharge">
            <option value="" data-i18n="reimburse.batch.none">None</option>
            <?php foreach($members as $m): ?>
            <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label" data-i18n="reimburse.batch.deadline">Deadline</label>
          <input type="date" name="deadline" class="form-control" id="batch-deadline" required>
        </div>
        <div class="mb-3">
          <label class="form-label" data-i18n="reimburse.batch.limit">Price Limit</label>
          <input type="number" step="0.01" name="price_limit" class="form-control" id="batch-limit">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="reimburse.batch.cancel">Cancel</button>
        <button type="submit" class="btn btn-primary" data-i18n="reimburse.batch.save">Save</button>
      </div>
    </form>
  </div>
</div>
<script>
  document.querySelectorAll('.edit-batch').forEach(btn=>{
    btn.addEventListener('click',()=>{
      document.getElementById('batch-id').value=btn.dataset.id;
      document.getElementById('batch-title').value=btn.dataset.title;
      document.getElementById('batch-incharge').value=btn.dataset.incharge;
      document.getElementById('batch-deadline').value=btn.dataset.deadline;
      document.getElementById('batch-limit').value=btn.dataset.limit;
      document.getElementById('batchModalLabel').textContent=translations[document.documentElement.lang||'en']['reimburse.action_edit'];
      var modal=new bootstrap.Modal(document.getElementById('batchModal'));
      modal.show();
    });
  });
  document.getElementById('batchModal').addEventListener('hidden.bs.modal',()=>{
    document.getElementById('batch-id').value='';
    document.getElementById('batch-limit').value='';
    document.getElementById('batchModalLabel').textContent=translations[document.documentElement.lang||'en']['reimburse.add_batch'];
  });
</script>
<?php endif; ?>
<?php include 'footer.php'; ?>
