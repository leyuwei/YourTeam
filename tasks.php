<?php
include 'auth.php';
include 'header.php';
$is_manager = ($_SESSION['role'] === 'manager');
$status = $_GET['status'] ?? '';
if($status){
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE status=? ORDER BY id DESC');
    $stmt->execute([$status]);
    $tasks = $stmt->fetchAll();
} else {
    $tasks = $pdo->query('SELECT * FROM tasks ORDER BY id DESC')->fetchAll();
}
$pendStmt = $pdo->query("SELECT t.id,t.title,COUNT(a.id) cnt FROM tasks t JOIN task_affairs a ON t.id=a.task_id WHERE a.status='pending' GROUP BY t.id");
$pending_affairs = $pendStmt->fetchAll();
$pendingTaskMap = [];
foreach($pending_affairs as $pending){
    $pendingTaskMap[$pending['id']] = (int)$pending['cnt'];
}
?>
<div class="d-flex justify-content-between mb-3">
  <h2 class="bold-target" data-i18n="tasks.title">Tasks Assignment</h2>
  <?php if($is_manager): ?>
  <button type="button" class="btn btn-success" id="addTaskBtn" data-i18n="tasks.add">New Task</button>
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
<tr><th data-i18n="tasks.table_title">Title</th><th data-i18n="tasks.table_start">Start</th><th data-i18n="tasks.table_status">Status</th><th data-i18n="tasks.table_actions">Actions</th></tr>
<?php foreach($tasks as $t): ?>
<?php $hasPendingWorkload = !empty($pendingTaskMap[$t['id']]); ?>
<tr<?= $hasPendingWorkload ? ' class="task-row-pending"' : ''; ?>>
  <td class="bold-target"><?= htmlspecialchars($t['title']); ?></td>
  <td><?= htmlspecialchars($t['start_date']); ?></td>
  <td data-i18n="tasks.status.<?= htmlspecialchars($t['status']); ?>"><?= htmlspecialchars($t['status']); ?></td>
  <td>
    <?php if($is_manager): ?>
    <button type="button" class="btn btn-sm btn-primary btn-edit-task" data-id="<?= $t['id']; ?>" data-i18n="tasks.action_edit">Edit</button>
    <?php endif; ?>
    <a class="btn btn-sm btn-warning" href="task_affairs.php?id=<?= $t['id']; ?>" data-i18n="tasks.action_affairs">Affairs</a>
    <button type="button" class="btn btn-sm btn-info qr-btn" data-url="task_member_fill.php?task_id=<?= $t['id']; ?>" data-i18n="tasks.action_fill">Self Fill</button>
    <?php if($is_manager): ?>
    <a class="btn btn-sm btn-danger delete-task" href="task_delete.php?id=<?= $t['id']; ?>" data-i18n="tasks.action_delete">Delete</a>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php if($is_manager): ?>
<div class="modal fade" id="taskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="taskModalTitle" data-i18n="task_edit.title_add">Add Task</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="taskError"></div>
        <form id="taskForm">
          <input type="hidden" id="taskId">
          <div class="mb-3">
            <label class="form-label" data-i18n="tasks.table_title">Title</label>
            <input type="text" class="form-control" id="taskTitle" required>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="task_edit.label_description">Description</label>
            <textarea class="form-control" id="taskDescription" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="task_edit.label_start">Start Date</label>
            <input type="date" class="form-control" id="taskStart">
          </div>
          <div class="mb-3">
            <label class="form-label" data-i18n="task_edit.label_status">Status</label>
            <select class="form-select" id="taskStatus">
              <option value="active" data-i18n="tasks.status.active">Active</option>
              <option value="paused" data-i18n="tasks.status.paused">Paused</option>
              <option value="finished" data-i18n="tasks.status.finished">Finished</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="task_edit.cancel">Cancel</button>
        <button type="submit" class="btn btn-primary" form="taskForm" data-i18n="task_edit.save">Save</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const langKey = () => document.documentElement.lang || 'zh';
  const t = (key, fallback = '') => {
    try {
      return translations?.[langKey()]?.[key] ?? fallback;
    } catch (e) {
      return fallback;
    }
  };

  document.querySelectorAll('.delete-task').forEach(link=>{
    link.addEventListener('click',e=>{
      const msg=t('tasks.confirm.delete','Delete this task?');
      if(!doubleConfirm(msg)) e.preventDefault();
    });
  });

  document.getElementById('boldToggle').addEventListener('change', function(){
    document.querySelectorAll('.bold-target').forEach(el => {
      el.classList.toggle('fw-bold', this.checked);
    });
  });

  <?php if($is_manager): ?>
  const taskModalEl=document.getElementById('taskModal');
  const taskModal=taskModalEl?new bootstrap.Modal(taskModalEl):null;
  const taskForm=document.getElementById('taskForm');
  const taskError=document.getElementById('taskError');
  const taskModalTitle=document.getElementById('taskModalTitle');
  const taskIdField=document.getElementById('taskId');
  const taskTitle=document.getElementById('taskTitle');
  const taskDescription=document.getElementById('taskDescription');
  const taskStart=document.getElementById('taskStart');
  const taskStatus=document.getElementById('taskStatus');

  function resetTaskForm(){
    if(!taskForm) return;
    taskForm.reset();
    taskIdField.value='';
    if(taskError){
      taskError.classList.add('d-none');
      taskError.textContent='';
    }
  }

  function setTaskModalTitle(key){
    if(!taskModalTitle) return;
    taskModalTitle.setAttribute('data-i18n',key);
    window.applyTranslations?.();
  }

  function openTaskModal(id=null){
    if(!taskModal) return;
    resetTaskForm();
    setTaskModalTitle(id ? 'task_edit.title_edit' : 'task_edit.title_add');
    const url=id?`task_edit.php?id=${id}`:'task_edit.php';
    fetch(url,{headers:{'Accept':'application/json'}})
      .then(resp=>resp.json())
      .then(data=>{
        if(!data.success){throw new Error('load');}
        const info=data.task||{};
        taskIdField.value=info.id||'';
        taskTitle.value=info.title||'';
        taskDescription.value=info.description||'';
        taskStart.value=info.start_date||'';
        taskStatus.value=info.status||'active';
        taskModal.show();
        window.applyTranslations?.();
      })
      .catch(()=>{
        if(taskError){
          taskError.textContent=t('task_edit.load_failed','Failed to load task.');
          taskError.classList.remove('d-none');
        }
        taskModal.show();
      });
  }

  const addTaskBtn=document.getElementById('addTaskBtn');
  addTaskBtn?.addEventListener('click',()=>openTaskModal());
  document.querySelectorAll('.btn-edit-task').forEach(btn=>{
    btn.addEventListener('click',()=>openTaskModal(btn.dataset.id));
  });

  taskForm?.addEventListener('submit',ev=>{
    ev.preventDefault();
    if(taskError){
      taskError.classList.add('d-none');
      taskError.textContent='';
    }
    const payload={
      id: taskIdField.value || undefined,
      title: taskTitle.value,
      description: taskDescription.value,
      start_date: taskStart.value,
      status: taskStatus.value
    };
    fetch('task_edit.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify(payload)
    }).then(resp=>resp.json()).then(data=>{
      if(!data.success){throw new Error(data.message||'save');}
      taskModal.hide();
      if(data.redirect){
        window.location.href=data.redirect;
      }else{
        window.location.reload();
      }
    }).catch(err=>{
      if(!taskError) return;
      const message = err.message==='save'?t('task_edit.error_generic','Failed to save task.'):err.message;
      taskError.textContent=message;
      taskError.classList.remove('d-none');
    });
  });
  <?php endif; ?>
});
</script>
<?php include 'footer.php'; ?>
