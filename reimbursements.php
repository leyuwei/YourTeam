<?php
include 'auth.php';
include 'reimbursement_log.php';
include 'header.php';
$is_manager = ($_SESSION['role'] === 'manager');
$member_id = $_SESSION['member_id'] ?? null;

if($is_manager && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])){
    $id = $_POST['id'] ?? '';
    $title = trim($_POST['title']);
    $incharge = $_POST['in_charge'] ?: null;
    $deadline = $_POST['deadline'];
    $limit = $_POST['price_limit'] !== '' ? $_POST['price_limit'] : null;
    $allowed = isset($_POST['allowed_types']) ? implode(',', $_POST['allowed_types']) : null;
    if($id){
        $oldStmt = $pdo->prepare("SELECT title,in_charge_member_id,deadline,price_limit,allowed_types FROM reimbursement_batches WHERE id=?");
        $oldStmt->execute([$id]);
        $old = $oldStmt->fetch();
        $stmt = $pdo->prepare("UPDATE reimbursement_batches SET title=?, in_charge_member_id=?, deadline=?, price_limit=?, allowed_types=? WHERE id=?");
        $stmt->execute([$title, $incharge, $deadline, $limit, $allowed, $id]);
        if($old){
            if($old['title'] !== $title) add_batch_log($pdo,$id,$_SESSION['username'],'Title changed from '.$old['title'].' to '.$title);
            if($old['deadline'] !== $deadline) add_batch_log($pdo,$id,$_SESSION['username'],'Deadline changed from '.$old['deadline'].' to '.$deadline);
            if($old['price_limit'] != $limit) add_batch_log($pdo,$id,$_SESSION['username'],'Price limit changed from '.$old['price_limit'].' to '.$limit);
            if($old['allowed_types'] !== $allowed) add_batch_log($pdo,$id,$_SESSION['username'],'Allowed types changed');
            if($old['in_charge_member_id'] != $incharge){
                $oldName='None';
                if($old['in_charge_member_id']){ $s=$pdo->prepare('SELECT name FROM members WHERE id=?'); $s->execute([$old['in_charge_member_id']]); $oldName=$s->fetchColumn()?:'None'; }
                $newName='None';
                if($incharge){ $s=$pdo->prepare('SELECT name FROM members WHERE id=?'); $s->execute([$incharge]); $newName=$s->fetchColumn()?:'None'; }
                add_batch_log($pdo,$id,$_SESSION['username'],'In charge changed from '.$oldName.' to '.$newName);
            }
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO reimbursement_batches (title, in_charge_member_id, deadline, price_limit, allowed_types) VALUES (?,?,?,?,?)");
        $stmt->execute([$title, $incharge, $deadline, $limit, $allowed]);
        $newId=$pdo->lastInsertId();
        add_batch_log($pdo,$newId,$_SESSION['username'],'Batch created');
    }
}

$batches = $pdo->query("SELECT b.*, m.name AS in_charge_name, (SELECT COUNT(*) FROM reimbursement_receipts r WHERE r.batch_id=b.id) AS receipt_count FROM reimbursement_batches b LEFT JOIN members m ON b.in_charge_member_id=m.id ORDER BY (b.status='completed'), b.deadline ASC")->fetchAll();
$activeBatches = [];
$completedBatches = [];
foreach($batches as $batch){
    if($batch['status'] === 'completed'){
        $completedBatches[] = $batch;
    } else {
        $activeBatches[] = $batch;
    }
}
$members = $pdo->query("SELECT id, name FROM members ORDER BY name")->fetchAll();
$announcement = $pdo->query("SELECT content_en, content_zh FROM reimbursement_announcement WHERE id=1")
                ->fetch(PDO::FETCH_ASSOC);
?>
<?php if(($announcement['content_en'] ?? '') || ($announcement['content_zh'] ?? '') || $is_manager): ?>
<div class="alert alert-warning">
  <?php if(($announcement['content_en'] ?? '') || ($announcement['content_zh'] ?? '')): ?>
  <div class="announcement" data-lang="en"><?= $announcement['content_en']; ?></div>
  <div class="announcement" data-lang="zh"><?= $announcement['content_zh']; ?></div>
  <?php endif; ?>
  <?php if($is_manager): ?>
  <a href="reimbursement_announcement_edit.php" class="btn btn-sm btn-light ms-3" data-i18n="reimburse.announcement.edit">Edit Announcement</a>
  <?php endif; ?>
</div>
<style>
.announcement[data-lang]{display:none;}
html[lang="en"] .announcement[data-lang="en"]{display:block;}
html[lang="zh"] .announcement[data-lang="zh"]{display:block;}
</style>
<?php endif; ?>
<div class="d-flex justify-content-between mb-3">
  <h2 data-i18n="reimburse.title">Reimbursement Batches</h2>
  <div>
    <?php if($is_manager): ?>
    <a class="btn btn-secondary" href="reimbursement_keywords.php" data-i18n="reimburse.keywords.manage">Keywords</a>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#batchModal" data-i18n="reimburse.add_batch">Add Batch</button>
    <?php endif; ?>
  </div>
</div>
<style>
  .table tr.reimbursement-row.batch-completed > * {
    background-color: var(--reimburse-batch-completed-bg) !important;
  }
  .table tr.reimbursement-row.batch-locked > * {
    background-color: var(--reimburse-batch-locked-bg) !important;
  }
  .table tr.reimbursement-row.batch-completed:hover > * {
    background-color: color-mix(in srgb, var(--reimburse-batch-completed-bg) 60%, transparent) !important;
  }
  .table tr.reimbursement-row.batch-locked:hover > * {
    background-color: color-mix(in srgb, var(--reimburse-batch-locked-bg) 65%, transparent) !important;
  }
</style>
<table class="table table-bordered">
<tr><th data-i18n="reimburse.table_title">Title</th><th data-i18n="reimburse.table_deadline">Deadline</th><th data-i18n="reimburse.table_incharge">In Charge</th><th data-i18n="reimburse.batch.status">Status</th><th data-i18n="reimburse.batch.limit">Limit</th><th data-i18n="reimburse.batch.allowed_types">Allowed Types</th><?php if(!$is_manager) echo '<th data-i18n="reimburse.table_myreceipts">My Receipts</th>'; ?><th data-i18n="reimburse.table_actions">Actions</th></tr>
<?php if(empty($activeBatches)): ?>
<tr><td colspan="<?= $is_manager ? 7 : 8; ?>" data-i18n="reimburse.active.none">No active batches</td></tr>
<?php endif; ?>
<?php foreach($activeBatches as $b): ?>
<?php
  $statusClass = '';
  if ($b['status'] === 'completed') {
      $statusClass = 'batch-completed';
  } elseif ($b['status'] === 'locked') {
      $statusClass = 'batch-locked';
  }
?>
<tr class="reimbursement-row <?= $statusClass; ?>">
  <td><?= htmlspecialchars($b['title']); ?></td>
  <td><?= htmlspecialchars($b['deadline']); ?></td>
  <td><?= htmlspecialchars($b['in_charge_name']); ?></td>
  <td><span data-i18n="reimburse.status.<?= $b['status']; ?>"><?= htmlspecialchars($b['status']); ?></span></td>
  <td><?= htmlspecialchars($b['price_limit']); ?></td>
  <td>
    <?php
      if($b['allowed_types']){
        $types = explode(',', $b['allowed_types']);
        foreach($types as $t){
          echo '<span data-i18n="reimburse.category.'.$t.'">'.$t.'</span> ';
        }
      } else {
        echo '<span data-i18n="reimburse.batch.none">None</span>';
      }
    ?>
  </td>
  <?php if(!$is_manager): ?>
  <td>
    <?php
      $stmt = $pdo->prepare("SELECT * FROM reimbursement_receipts WHERE batch_id=? AND member_id=? AND status<>'refused' ORDER BY id DESC");
      $stmt->execute([$b['id'],$member_id]);
      $urs = $stmt->fetchAll();
      if($urs){
        echo '<ul class="list-group list-group-flush mb-0">';
        foreach($urs as $r){
          echo '<li class="list-group-item px-2 py-1"><a href="reimburse_uploads/'.$b['id'].'/'.urlencode($r['stored_filename']).'" target="_blank">'.htmlspecialchars($r['stored_filename']).'</a><br><small>'.htmlspecialchars($r['description']).' - <span data-i18n="reimburse.category.'.$r['category'].'">'.htmlspecialchars($r['category']).'</span> - '.htmlspecialchars($r['price']).' - '.htmlspecialchars($r['uploaded_at']).'</small></li>';
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
    <button class="btn btn-sm btn-warning edit-batch" data-id="<?= $b['id']; ?>" data-title="<?= htmlspecialchars($b['title'],ENT_QUOTES); ?>" data-incharge="<?= $b['in_charge_member_id']; ?>" data-deadline="<?= $b['deadline']; ?>" data-limit="<?= $b['price_limit']; ?>" data-types="<?= htmlspecialchars($b['allowed_types']); ?>" data-i18n="reimburse.action_edit">Edit</button>
    <a class="btn btn-sm btn-danger" href="reimbursement_batch_delete.php?id=<?= $b['id']; ?>" data-i18n="reimburse.batch.delete" onclick="return doubleConfirm(translations[document.documentElement.lang||'zh']['reimburse.batch.confirm_delete_batch']);">Delete</a>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</table>

<?php if(!empty($completedBatches)): ?>
<div class="mt-4">
  <button class="btn btn-outline-secondary" type="button" id="toggleCompletedBatches" data-bs-toggle="collapse" data-bs-target="#completedBatches" aria-expanded="false" aria-controls="completedBatches" data-i18n="reimburse.completed.show">Show completed batches</button>
  <div class="collapse mt-3" id="completedBatches">
    <h3 data-i18n="reimburse.completed.title">Completed Batches</h3>
    <table class="table table-bordered">
    <tr><th data-i18n="reimburse.table_title">Title</th><th data-i18n="reimburse.table_deadline">Deadline</th><th data-i18n="reimburse.table_incharge">In Charge</th><th data-i18n="reimburse.batch.status">Status</th><th data-i18n="reimburse.batch.limit">Limit</th><th data-i18n="reimburse.batch.allowed_types">Allowed Types</th><?php if(!$is_manager) echo '<th data-i18n="reimburse.table_myreceipts">My Receipts</th>'; ?><th data-i18n="reimburse.table_actions">Actions</th></tr>
    <?php foreach($completedBatches as $b): ?>
    <?php
      $statusClass = '';
      if ($b['status'] === 'completed') {
          $statusClass = 'batch-completed';
      } elseif ($b['status'] === 'locked') {
          $statusClass = 'batch-locked';
      }
    ?>
    <tr class="reimbursement-row <?= $statusClass; ?>">
      <td><?= htmlspecialchars($b['title']); ?></td>
      <td><?= htmlspecialchars($b['deadline']); ?></td>
      <td><?= htmlspecialchars($b['in_charge_name']); ?></td>
      <td><span data-i18n="reimburse.status.<?= $b['status']; ?>"><?= htmlspecialchars($b['status']); ?></span></td>
      <td><?= htmlspecialchars($b['price_limit']); ?></td>
      <td>
        <?php
          if($b['allowed_types']){
            $types = explode(',', $b['allowed_types']);
            foreach($types as $t){
              echo '<span data-i18n="reimburse.category.'.$t.'">'.$t.'</span> ';
            }
          } else {
            echo '<span data-i18n="reimburse.batch.none">None</span>';
          }
        ?>
      </td>
      <?php if(!$is_manager): ?>
      <td>
        <?php
          $stmt = $pdo->prepare("SELECT * FROM reimbursement_receipts WHERE batch_id=? AND member_id=? AND status<>'refused' ORDER BY id DESC");
          $stmt->execute([$b['id'],$member_id]);
          $urs = $stmt->fetchAll();
          if($urs){
            echo '<ul class="list-group list-group-flush mb-0">';
            foreach($urs as $r){
              echo '<li class="list-group-item px-2 py-1"><a href="reimburse_uploads/'.$b['id'].'/'.urlencode($r['stored_filename']).'" target="_blank">'.htmlspecialchars($r['stored_filename']).'</a><br><small>'.htmlspecialchars($r['description']).' - <span data-i18n="reimburse.category.'.$r['category'].'">'.htmlspecialchars($r['category']).'</span> - '.htmlspecialchars($r['price']).' - '.htmlspecialchars($r['uploaded_at']).'</small></li>';
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
        <button class="btn btn-sm btn-warning edit-batch" data-id="<?= $b['id']; ?>" data-title="<?= htmlspecialchars($b['title'],ENT_QUOTES); ?>" data-incharge="<?= $b['in_charge_member_id']; ?>" data-deadline="<?= $b['deadline']; ?>" data-limit="<?= $b['price_limit']; ?>" data-types="<?= htmlspecialchars($b['allowed_types']); ?>" data-i18n="reimburse.action_edit">Edit</button>
        <a class="btn btn-sm btn-danger" href="reimbursement_batch_delete.php?id=<?= $b['id']; ?>" data-i18n="reimburse.batch.delete" onclick="return doubleConfirm(translations[document.documentElement.lang||'zh']['reimburse.batch.confirm_delete_batch']);">Delete</a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </table>
  </div>
</div>
<?php endif; ?>
<script>
const completedToggle=document.getElementById('toggleCompletedBatches');
const completedContainer=document.getElementById('completedBatches');
if(completedToggle && completedContainer){
  const updateText=(state)=>{
    const lang=document.documentElement.lang||'zh';
    const key=state==='show' ? 'reimburse.completed.show' : 'reimburse.completed.hide';
    completedToggle.textContent=translations[lang][key];
  };
  completedContainer.addEventListener('show.bs.collapse',()=>updateText('hide'));
  completedContainer.addEventListener('hide.bs.collapse',()=>updateText('show'));
  updateText('show');
}
</script>
<?php if(!$is_manager): ?>
<?php
  $stmt = $pdo->prepare("SELECT r.*, b.title AS batch_title FROM reimbursement_receipts r JOIN reimbursement_batches b ON r.batch_id=b.id WHERE r.member_id=? AND r.status='refused' ORDER BY r.id DESC");
  $stmt->execute([$member_id]);
  $receipts = $stmt->fetchAll();
?>
<br>
<h3 data-i18n="reimburse.refused.title">Refused Receipts</h3>
<?php if($receipts): ?>
<table class="table table-bordered">
<tr><th data-i18n="reimburse.batch.receipt">Receipt</th><th data-i18n="reimburse.batch.category">Category</th><th data-i18n="reimburse.batch.description">Description</th><th data-i18n="reimburse.batch.price">Price</th><th data-i18n="reimburse.batch.upload_date">Upload Date</th><th data-i18n="reimburse.refused.original_batch">Original Batch</th><th data-i18n="reimburse.batch.actions">Actions</th></tr>
<?php foreach($receipts as $r): ?>
<tr>
  <td><a href="<?= 'reimburse_uploads/'.$r['batch_id'].'/'.urlencode($r['stored_filename']); ?>" target="_blank"><?= htmlspecialchars($r['stored_filename']); ?></a></td>
  <td><span data-i18n="reimburse.category.<?= $r['category']; ?>"><?= htmlspecialchars($r['category']); ?></span></td>
  <td><?= htmlspecialchars($r['description']); ?></td>
  <td><?= htmlspecialchars($r['price']); ?></td>
  <td><?= htmlspecialchars($r['uploaded_at']); ?></td>
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
        <div class="mb-3">
          <label class="form-label" data-i18n="reimburse.batch.allowed_types">Allowed Types</label>
          <?php $cats=['office','electronic','membership','book','trip']; foreach($cats as $c): ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="allowed_types[]" value="<?= $c; ?>" id="type-<?= $c; ?>">
            <label class="form-check-label" for="type-<?= $c; ?>" data-i18n="reimburse.category.<?= $c; ?>"><?= $c; ?></label>
          </div>
          <?php endforeach; ?>
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
      document.querySelectorAll('input[name="allowed_types[]"]').forEach(cb=>{cb.checked=false;});
      if(btn.dataset.types){
        btn.dataset.types.split(',').forEach(t=>{
          const el=document.getElementById('type-'+t);
          if(el) el.checked=true;
        });
      }
      document.getElementById('batchModalLabel').textContent=translations[document.documentElement.lang||'zh']['reimburse.action_edit'];
      var modal=new bootstrap.Modal(document.getElementById('batchModal'));
      modal.show();
    });
  });
  document.getElementById('batchModal').addEventListener('hidden.bs.modal',()=>{
    document.getElementById('batch-id').value='';
    document.getElementById('batch-limit').value='';
    document.querySelectorAll('input[name="allowed_types[]"]').forEach(cb=>{cb.checked=false;});
    document.getElementById('batchModalLabel').textContent=translations[document.documentElement.lang||'zh']['reimburse.add_batch'];
  });
</script>
<?php endif; ?>
<?php include 'footer.php'; ?>
