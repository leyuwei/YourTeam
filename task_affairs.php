<?php
include 'auth.php';
include 'header.php';

$task_id = $_GET['id'] ?? null;
if(!$task_id){
    header('Location: tasks.php');
    exit();
}

$task = $pdo->prepare('SELECT * FROM tasks WHERE id=?');
$task->execute([$task_id]);
$task = $task->fetch();

$affairs_stmt = $pdo->prepare('SELECT a.*, GROUP_CONCAT(CONCAT(m.name, " (", m.campus_id, ")") SEPARATOR ", ") AS members, GROUP_CONCAT(m.id) AS member_ids FROM task_affairs a LEFT JOIN task_affair_members am ON a.id=am.affair_id LEFT JOIN members m ON am.member_id=m.id WHERE a.task_id=? GROUP BY a.id ORDER BY a.start_time DESC');
$affairs_stmt->execute([$task_id]);
$affairs = $affairs_stmt->fetchAll();

$is_manager = ($_SESSION['role'] === 'manager');
$workload_ranking = [];
if($is_manager){
    $members = $pdo->query("SELECT id, campus_id, name FROM members WHERE status != 'exited' ORDER BY name")->fetchAll();
    $ranking_stmt = $pdo->prepare("SELECT m.id, m.name, m.campus_id, SUM(TIMESTAMPDIFF(DAY, a.start_time, a.end_time)) AS total_days FROM task_affairs a JOIN task_affair_members am ON a.id = am.affair_id JOIN members m ON am.member_id = m.id WHERE a.task_id = ? AND a.status = 'confirmed' AND m.status = 'in_work' GROUP BY m.id, m.name, m.campus_id HAVING total_days > 0 ORDER BY total_days DESC, m.name ASC");
    $ranking_stmt->execute([$task_id]);
    $workload_ranking = $ranking_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<h2><span data-i18n="task_affairs.title_prefix">Task Affairs - </span><?php echo htmlspecialchars($task['title']); ?></h2>
<?php if($is_manager): ?>
<div class="card mb-4">
  <div class="card-header" data-i18n="task_affairs.ranking.title">Task Workload Ranking</div>
  <div class="card-body p-0">
    <?php if($workload_ranking): ?>
    <div class="table-responsive">
      <table class="table table-bordered mb-0">
        <thead>
          <tr>
            <th data-i18n="task_affairs.ranking.rank">Rank</th>
            <th data-i18n="task_affairs.ranking.campus_id">Campus ID</th>
            <th data-i18n="task_affairs.ranking.member">Member</th>
            <th data-i18n="task_affairs.ranking.workload">Total Workload (days)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($workload_ranking as $index => $row): ?>
          <tr>
            <td><?= $index + 1; ?></td>
            <td><?= htmlspecialchars($row['campus_id']); ?></td>
            <td><?= htmlspecialchars($row['name']); ?></td>
            <td><?= htmlspecialchars(round($row['total_days'], 2)); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <p class="mb-0 p-3" data-i18n="task_affairs.ranking.empty">No workload records yet.</p>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php if($is_manager): ?>
<form method="post" action="affair_merge.php" id="mergeForm">
<input type="hidden" name="task_id" value="<?= $task_id; ?>">
<div class="mb-2">
  <button type="submit" class="btn btn-warning btn-sm" id="mergeBtn" disabled data-i18n="task_affairs.merge_selected">Merge Selected</button>
</div>
<?php endif; ?>
<table class="table table-bordered">
<tr>
  <?php if($is_manager): ?><th><input type="checkbox" id="selectAll"></th><?php endif; ?>
  <th data-i18n="task_affairs.table_description">Description</th>
  <th data-i18n="task_affairs.table_members">Members</th>
  <th data-i18n="task_affairs.table_start">Start Date</th>
  <th data-i18n="task_affairs.table_end">End Date</th>
  <th data-i18n="task_affairs.table_days">Days</th>
  <th data-i18n="task_affairs.table_status">Status</th>
  <?php if($is_manager): ?><th data-i18n="task_affairs.table_actions">Actions</th><?php endif; ?>
</tr>
<?php foreach($affairs as $a): ?>
<?php $days = (strtotime($a['end_time']) - strtotime($a['start_time']))/86400; ?>
<tr>
  <?php if($is_manager): ?><td><input type="checkbox" name="affair_ids[]" value="<?= $a['id']; ?>" class="affair-checkbox"></td><?php endif; ?>
  <td><?= htmlspecialchars($a['description']); ?></td>
  <td><?= htmlspecialchars($a['members']); ?></td>
  <td><?= htmlspecialchars(date('Y-m-d', strtotime($a['start_time']))); ?></td>
  <td><?= htmlspecialchars(date('Y-m-d', strtotime($a['end_time'] . ' -1 day'))); ?></td>
  <td><?= htmlspecialchars($days); ?></td>
  <td data-i18n="task_affairs.status.<?= $a['status']; ?>"><?= htmlspecialchars($a['status']); ?></td>
  <?php if($is_manager): ?>
  <td>
    <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#editModal<?= $a['id']; ?>" data-i18n="task_affairs.action_edit">Edit</button>
    <a class="btn btn-sm btn-danger delete-affair" href="affair_delete.php?id=<?= $a['id']; ?>&task_id=<?= $task_id; ?>" data-i18n="task_affairs.action_delete">Delete</a>
    <?php if($a['status']==='pending'): ?>
    <a class="btn btn-sm btn-success" href="affair_confirm.php?id=<?= $a['id']; ?>&task_id=<?= $task_id; ?>&status=confirmed" data-i18n="task_affairs.action_confirm">Confirm</a>
    <?php else: ?>
    <a class="btn btn-sm btn-warning" href="affair_confirm.php?id=<?= $a['id']; ?>&task_id=<?= $task_id; ?>&status=pending" data-i18n="task_affairs.action_unconfirm">Unconfirm</a>
    <?php endif; ?>
  </td>
  <?php endif; ?>
</tr>
<?php endforeach; ?>
</table>
<?php if($is_manager): ?>
</form>
<?php else: ?>
<a class="btn btn-primary" href="task_member_fill.php?task_id=<?= $task_id; ?>" data-i18n="tasks.action_fill">Self Fill</a>
<?php endif; ?>
<?php if($is_manager): ?>
<?php foreach($affairs as $a): $selected = $a['member_ids'] ? explode(',', $a['member_ids']) : []; ?>
<div class="modal fade" id="editModal<?= $a['id']; ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="affair_edit.php" class="edit-affair-form">
        <div class="modal-header">
          <h5 class="modal-title" data-i18n="task_affairs.edit_title">Edit Affair</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $a['id']; ?>">
          <input type="hidden" name="task_id" value="<?= $task_id; ?>">
          <div class="mb-3">
            <label class="form-label" data-i18n="task_affairs.label_description">Description</label>
            <textarea name="description" class="form-control" rows="2" required><?= htmlspecialchars($a['description']); ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="task_affairs.label_members">Members (hold Ctrl to select multiple)</label>
            <select name="member_ids[]" class="form-select" multiple required size="8">
              <?php foreach($members as $m): ?>
              <option value="<?= $m['id']; ?>" <?= in_array($m['id'], $selected) ? 'selected' : ''; ?>><?= htmlspecialchars($m['name']); ?> (<?= $m['campus_id']; ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="task_affairs.label_start">Start Date</label>
            <input type="date" name="start_time" class="form-control edit-start" required value="<?= date('Y-m-d', strtotime($a['start_time'])); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="task_affairs.label_end">End Date</label>
            <input type="date" name="end_time" class="form-control edit-end" required value="<?= date('Y-m-d', strtotime($a['end_time'] . ' -1 day')); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="task_affairs.label_status">Status</label>
            <select name="status" class="form-select">
              <option value="pending" data-i18n="task_affairs.status.pending" <?= $a['status']==='pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="confirmed" data-i18n="task_affairs.status.confirmed" <?= $a['status']==='confirmed' ? 'selected' : ''; ?>>Confirmed</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" data-i18n="task_affairs.save">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="task_affairs.cancel">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>
<br><br>
<h4 data-i18n="task_affairs.new_title">New Affair</h4>
<form method="post" action="affair_add.php">
  <input type="hidden" name="task_id" value="<?= $task_id; ?>">
  <div class="mb-3">
    <label class="form-label" data-i18n="task_affairs.label_description">Description</label>
    <textarea name="description" class="form-control" rows="2" required></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="task_affairs.label_members">Members (hold Ctrl to select multiple)</label>
    <select name="member_ids[]" class="form-select" multiple required size="10">
      <?php foreach($members as $m): ?>
      <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['name']); ?> (<?= $m['campus_id']; ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="task_affairs.label_start">Start Date</label>
    <input type="date" name="start_time" id="startDate" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="task_affairs.label_end">End Date</label>
    <input type="date" name="end_time" id="endDate" class="form-control" required>
    <div id="dayCount" class="mt-2"></div>
  </div>
  <button type="submit" class="btn btn-primary" data-i18n="task_affairs.add">Add Affair</button>
  <a href="tasks.php" class="btn btn-secondary" data-i18n="task_affairs.back">Back</a>
</form>
<script>
const affairForm = document.querySelector('form[action="affair_add.php"]');
const startField = document.getElementById('startDate');
const endField = document.getElementById('endDate');
const dayCount = document.getElementById('dayCount');
const getLang = () => document.documentElement.lang || 'zh';
function updateDays(){
  if(startField.value && endField.value){
    const start = new Date(startField.value);
    const end = new Date(endField.value);
    const diff = Math.floor((end - start) / (1000*60*60*24)) + 1;
    if(diff <= 0){
      const msg = translations[getLang()]['task_affairs.error.range'];
      alert(msg);
      endField.value = '';
      dayCount.textContent = '';
      return false;
    }
    const lang = getLang();
    dayCount.textContent = translations[lang]['task_affairs.workload_prefix'] + diff + translations[lang]['task_affairs.workload_suffix'];
  } else {
    dayCount.textContent = '';
  }
  return true;
}
startField.addEventListener('change', updateDays);
endField.addEventListener('change', updateDays);
affairForm.addEventListener('submit', function(e){
  if(!updateDays()){
    e.preventDefault();
  }
});

document.querySelectorAll('.edit-affair-form').forEach(function(form){
  form.addEventListener('submit', function(e){
    const s = form.querySelector('.edit-start').value;
    const ed = form.querySelector('.edit-end').value;
    if(s && ed && new Date(ed) < new Date(s)){
      e.preventDefault();
      const msg = translations[getLang()]['task_affairs.error.range'];
      alert(msg);
    }
  });
});

document.getElementById('selectAll').addEventListener('change', function(){
  document.querySelectorAll('.affair-checkbox').forEach(cb => cb.checked = this.checked);
  document.getElementById('mergeBtn').disabled = !this.checked;
});
document.querySelectorAll('.affair-checkbox').forEach(cb => {
  cb.addEventListener('change', () => {
    const anyChecked = Array.from(document.querySelectorAll('.affair-checkbox')).some(c=>c.checked);
    document.getElementById('mergeBtn').disabled = !anyChecked;
  });
});
</script>
<?php endif; ?>
<?php include 'footer.php'; ?>
