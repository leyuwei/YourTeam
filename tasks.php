<?php
include 'auth.php';
include 'header.php';
$is_manager = ($_SESSION['role'] === 'manager');
$status = $_GET['status'] ?? '';
if($status){
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE status=? ORDER BY sort_order ASC, id DESC');
    $stmt->execute([$status]);
    $tasks = $stmt->fetchAll();
} else {
    $tasks = $pdo->query('SELECT * FROM tasks ORDER BY sort_order ASC, id DESC')->fetchAll();
}
$runningTasks = [];
$finishedTasks = [];
if($status === ''){
    foreach($tasks as $task){
        if(($task['status'] ?? '') === 'finished'){
            $finishedTasks[] = $task;
        } else {
            $runningTasks[] = $task;
        }
    }
} else {
    $runningTasks = $tasks;
}
$pendStmt = $pdo->query("SELECT t.id,t.title,COUNT(a.id) cnt FROM tasks t JOIN task_affairs a ON t.id=a.task_id WHERE a.status='pending' GROUP BY t.id");
$pending_affairs = $pendStmt->fetchAll();
$pendingTaskMap = [];
foreach($pending_affairs as $pending){
    $pendingTaskMap[$pending['id']] = (int)$pending['cnt'];
}
?>
<style>
  .task-drag-handle { cursor:grab; text-align:center; width:2.5rem; }
  .task-drag-handle:active { cursor:grabbing; }
  .task-order-index { width:3rem; text-align:center; }
</style>
<div class="d-flex justify-content-between mb-3">
  <h2 class="bold-target" data-i18n="tasks.title">Tasks Assignment</h2>
  <?php if($is_manager): ?>
  <button type="button" class="btn btn-success" id="newTaskBtn" data-i18n="tasks.add">New Task</button>
  <?php endif; ?>
</div>
<form class="row g-3 mb-3" method="get">
  <div class="col-auto">
    <select name="status" class="form-select">
      <option value="" data-i18n="tasks.filter_all">All Status</option>
      <option value="active" <?= $status=='active'?'selected':''; ?> data-i18n="tasks.filter.active">Active</option>
      <option value="paused" <?= $status=='paused'?'selected':''; ?> data-i18n="tasks.filter.paused">Paused</option>
      <option value="finished" <?= $status=='finished'?'selected':''; ?> data-i18n="tasks.filter.finished">Finished</option>
    </select>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-primary" data-i18n="tasks.filter.button">Filter</button>
  </div>
</form>
<div class="form-check form-switch mb-3">
  <input class="form-check-input" type="checkbox" id="boldToggle">
  <label class="form-check-label" for="boldToggle" data-i18n="bold_font">Bold font</label>
</div>
<?php if($is_manager && !empty($pending_affairs)): ?>
<div class="alert alert-danger fw-bold">
  <span data-i18n="tasks.pending_warning">Unconfirmed member affairs, please confirm ASAP:</span>
  <ul class="mb-0">
    <?php foreach($pending_affairs as $p): ?>
    <li><?= htmlspecialchars($p['title']); ?> (<?= $p['cnt']; ?>)</li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<table class="table table-bordered">
<thead>
  <tr>
    <?php if($is_manager): ?>
    <th class="task-drag-handle"></th>
    <?php endif; ?>
    <th class="task-order-index">#</th>
    <th data-i18n="tasks.table_title">Title</th>
    <th data-i18n="tasks.table_description">Description</th>
    <th data-i18n="tasks.table_start">Start</th>
    <th data-i18n="tasks.table_status">Status</th>
    <th data-i18n="tasks.table_actions">Actions</th>
  </tr>
</thead>
<tbody id="taskTableBody">
<?php foreach($runningTasks as $index=>$t): ?>
<?php $hasPendingWorkload = !empty($pendingTaskMap[$t['id']]); ?>
<?php $canSelfFill = $is_manager || $t['status']==='active'; ?>
<tr data-id="<?= $t['id']; ?>"<?= $hasPendingWorkload ? ' class="task-row-pending"' : ''; ?>>
  <?php if($is_manager): ?>
  <td class="task-drag-handle">&#9776;</td>
  <?php endif; ?>
  <td class="task-order-index" data-task-order><?= $index + 1; ?></td>
  <td class="bold-target"><?= htmlspecialchars($t['title']); ?></td>
  <td><?= htmlspecialchars($t['description'] ?? ''); ?></td>
  <td><?= htmlspecialchars($t['start_date']); ?></td>
  <td data-i18n="tasks.status.<?= htmlspecialchars($t['status']); ?>"><?= htmlspecialchars($t['status']); ?></td>
  <td>
    <?php if($is_manager): ?>
    <button type="button" class="btn btn-sm btn-primary edit-task-btn"
            data-task-id="<?= $t['id']; ?>"
            data-task-title="<?= htmlspecialchars($t['title'], ENT_QUOTES); ?>"
            data-task-description="<?= htmlspecialchars($t['description'] ?? '', ENT_QUOTES); ?>"
            data-task-start="<?= htmlspecialchars($t['start_date']); ?>"
            data-task-status="<?= htmlspecialchars($t['status']); ?>"
            data-i18n="tasks.action_edit">Edit</button>
    <?php endif; ?>
    <a class="btn btn-sm btn-warning" href="task_affairs.php?id=<?= $t['id']; ?>" data-i18n="tasks.action_affairs">Affairs</a>
    <?php if($canSelfFill): ?>
    <button type="button" class="btn btn-sm btn-info qr-btn" data-url="task_member_fill.php?task_id=<?= $t['id']; ?>" data-i18n="tasks.action_fill">Self Fill</button>
    <?php else: ?>
    <button type="button" class="btn btn-sm btn-info" disabled title="任务已暂停或结束，无法申报">Self Fill</button>
    <?php endif; ?>
    <?php if($is_manager): ?>
    <a class="btn btn-sm btn-danger delete-task" href="task_delete.php?id=<?= $t['id']; ?>" data-i18n="tasks.action_delete">Delete</a>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php if($status === '' && !empty($finishedTasks)): ?>
<div class="accordion mb-3" id="finishedTasksAccordion">
  <div class="accordion-item">
    <h2 class="accordion-header" id="finishedTasksHeading">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#finishedTasksCollapse" aria-expanded="false" aria-controls="finishedTasksCollapse">
        已结束任务（<?= count($finishedTasks); ?>）
      </button>
    </h2>
    <div id="finishedTasksCollapse" class="accordion-collapse collapse" aria-labelledby="finishedTasksHeading" data-bs-parent="#finishedTasksAccordion">
      <div class="accordion-body px-0 pb-0">
        <table class="table table-bordered mb-0">
          <thead>
            <tr>
              <th class="task-order-index">#</th>
              <th data-i18n="tasks.table_title">Title</th>
              <th data-i18n="tasks.table_description">Description</th>
              <th data-i18n="tasks.table_start">Start</th>
              <th data-i18n="tasks.table_status">Status</th>
              <th data-i18n="tasks.table_actions">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($finishedTasks as $index=>$t): ?>
          <?php $hasPendingWorkload = !empty($pendingTaskMap[$t['id']]); ?>
          <tr<?= $hasPendingWorkload ? ' class="task-row-pending"' : ''; ?>>
            <td class="task-order-index"><?= $index + 1; ?></td>
            <td class="bold-target"><?= htmlspecialchars($t['title']); ?></td>
            <td><?= htmlspecialchars($t['description'] ?? ''); ?></td>
            <td><?= htmlspecialchars($t['start_date']); ?></td>
            <td data-i18n="tasks.status.<?= htmlspecialchars($t['status']); ?>"><?= htmlspecialchars($t['status']); ?></td>
            <td>
              <?php if($is_manager): ?>
              <button type="button" class="btn btn-sm btn-primary edit-task-btn"
                      data-task-id="<?= $t['id']; ?>"
                      data-task-title="<?= htmlspecialchars($t['title'], ENT_QUOTES); ?>"
                      data-task-description="<?= htmlspecialchars($t['description'] ?? '', ENT_QUOTES); ?>"
                      data-task-start="<?= htmlspecialchars($t['start_date']); ?>"
                      data-task-status="<?= htmlspecialchars($t['status']); ?>"
                      data-i18n="tasks.action_edit">Edit</button>
              <?php endif; ?>
              <a class="btn btn-sm btn-warning" href="task_affairs.php?id=<?= $t['id']; ?>" data-i18n="tasks.action_affairs">Affairs</a>
              <button type="button" class="btn btn-sm btn-info" disabled title="任务已暂停或结束，无法申报">Self Fill</button>
              <?php if($is_manager): ?>
              <a class="btn btn-sm btn-danger delete-task" href="task_delete.php?id=<?= $t['id']; ?>" data-i18n="tasks.action_delete">Delete</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<?php if($is_manager): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const taskModalEl = document.getElementById('taskModal');
  const taskModal = taskModalEl ? new bootstrap.Modal(taskModalEl) : null;
  const taskForm = document.getElementById('taskForm');
  const modalTitle = document.getElementById('taskModalLabel');
  const titleInput = document.getElementById('taskTitle');
  const descInput = document.getElementById('taskDescription');
  const startInput = document.getElementById('taskStart');
  const statusSelect = document.getElementById('taskStatus');
  const taskTableBody = document.getElementById('taskTableBody');
  const isManager = <?= $is_manager ? 'true' : 'false'; ?>;

  document.querySelectorAll('.delete-task').forEach(link=>{
    link.addEventListener('click',e=>{
      const lang=document.documentElement.lang||'zh';
      const msg=translations[lang]['tasks.confirm.delete'];
      if(!doubleConfirm(msg)) e.preventDefault();
    });
  });

  document.getElementById('boldToggle').addEventListener('change', function(){
    document.querySelectorAll('.bold-target').forEach(el => {
      el.classList.toggle('fw-bold', this.checked);
    });
  });

  function setModalTitle(key){
    const lang=document.documentElement.lang||'zh';
    modalTitle.textContent = translations?.[lang]?.[key] || modalTitle.textContent;
  }

  function openTaskModal({id=null,title='',description='',start_date='',status='active'}){
    if(!taskModal || !taskForm) return;
    taskForm.action = 'task_edit.php' + (id ? `?id=${id}` : '');
    titleInput.value = title;
    descInput.value = description;
    startInput.value = start_date;
    statusSelect.value = status || 'active';
    setModalTitle(id ? 'task_edit.title_edit' : 'task_edit.title_add');
    taskModal.show();
  }

  const newBtn=document.getElementById('newTaskBtn');
  if(newBtn){
    newBtn.addEventListener('click',()=>{
      openTaskModal({});
    });
  }

  document.querySelectorAll('.edit-task-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
      openTaskModal({
        id: btn.dataset.taskId,
        title: btn.dataset.taskTitle || '',
        description: btn.dataset.taskDescription || '',
        start_date: btn.dataset.taskStart || '',
        status: btn.dataset.taskStatus || 'active'
      });
    });
  });

  const updateTaskOrderNumbers = () => {
    document.querySelectorAll('[data-task-order]').forEach((cell, index) => {
      cell.textContent = index + 1;
    });
  };

  if (isManager && typeof Sortable !== 'undefined' && taskTableBody) {
    Sortable.create(taskTableBody, {
      handle: '.task-drag-handle',
      animation: 150,
      onEnd: () => {
        updateTaskOrderNumbers();
        const order = Array.from(taskTableBody.querySelectorAll('tr')).map((row, index) => ({
          id: row.dataset.id,
          position: index
        }));
        fetch('task_order.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({order})
        });
      }
    });
  }
});
</script>
<div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="taskModalLabel" data-i18n="task_edit.title_add">Add Task</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" id="taskForm" action="task_edit.php">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" for="taskTitle" data-i18n="tasks.table_title">Title</label>
            <input type="text" id="taskTitle" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="taskDescription" data-i18n="task_edit.label_description">Description</label>
            <textarea id="taskDescription" name="description" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" for="taskStart" data-i18n="task_edit.label_start">Start Date</label>
            <input type="date" id="taskStart" name="start_date" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label" for="taskStatus" data-i18n="task_edit.label_status">Status</label>
            <select id="taskStatus" name="status" class="form-select">
              <option value="active" data-i18n="tasks.status.active">Active</option>
              <option value="paused" data-i18n="tasks.status.paused">Paused</option>
              <option value="finished" data-i18n="tasks.status.finished">Finished</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" data-i18n="task_edit.save">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="task_edit.cancel">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include 'footer.php'; ?>
