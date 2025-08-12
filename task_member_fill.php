<?php
require 'config.php';
$task_id = $_GET['task_id'] ?? null;
if(!$task_id){
    echo 'Invalid task id';
    exit();
}

$taskStmt = $pdo->prepare('SELECT title FROM tasks WHERE id=?');
$taskStmt->execute([$task_id]);
$taskTitle = $taskStmt->fetchColumn();
if (!$taskTitle) { echo 'Task not found'; exit(); }
if(isset($_SESSION['fill_task_id']) && $_SESSION['fill_task_id'] != $task_id){
    unset($_SESSION['fill_task_id'], $_SESSION['fill_member_id']);
}
$member_id = $_SESSION['fill_member_id'] ?? null;
$error = '';
$msg = '';
if(!$member_id && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify'){
    $name = $_POST['name'] ?? '';
    $identity = $_POST['identity_number'] ?? '';
    $stmt = $pdo->prepare('SELECT id FROM members WHERE name=? AND identity_number=?');
    $stmt->execute([$name,$identity]);
    $member = $stmt->fetch();
    if($member){
        $_SESSION['fill_member_id'] = $member['id'];
        $_SESSION['fill_task_id'] = $task_id;
        $member_id = $member['id'];
    } else {
        $error = '身份验证失败';
    }
}
if($member_id && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add'){
    $description = $_POST['description'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    if(strtotime($end_time) <= strtotime($start_time)){
        $error = '结束时间必须晚于起始时间';
    } else {
        $stmt = $pdo->prepare('INSERT INTO task_affairs(task_id,description,start_time,end_time) VALUES (?,?,?,?)');
        $stmt->execute([$task_id,$description,$start_time,$end_time]);
        $affair_id = $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO task_affair_members(affair_id,member_id) VALUES (?,?)')->execute([$affair_id,$member_id]);
        $msg = '已提交';
    }
}
$affairs = [];
if($member_id){
    $stmt = $pdo->prepare('SELECT a.description,a.start_time,a.end_time,GROUP_CONCAT(m.name SEPARATOR ", ") AS members FROM task_affairs a LEFT JOIN task_affair_members am ON a.id=am.affair_id LEFT JOIN members m ON am.member_id=m.id WHERE a.task_id=? GROUP BY a.id ORDER BY a.start_time DESC');
    $stmt->execute([$task_id]);
    $affairs = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>团队成员工作事务申报</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .container { max-width: 80%; }
</style>
</head>
<body class="container py-5">
<h2>工作量报备 - 与绩效挂钩！</h2>
<h4><span style="color:black">您正在申报：<?= htmlspecialchars($taskTitle); ?>方面的工作</span></h4>
<br>
<?php if(!$member_id): ?>
<form method="post" class="mt-4">
  <input type="hidden" name="action" value="verify">
  <div class="mb-3">
    <label class="form-label">姓名</label>
    <input type="text" name="name" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">身份证号码</label>
    <input type="text" name="identity_number" class="form-control" required>
  </div>
  <?php if($error): ?><div class="text-danger mb-3"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <button type="submit" class="btn btn-primary">验证身份</button>
</form>
<?php else: ?>
<?php if($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if($error && !$msg): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<h4><b>已填工作事务</b></h4>
<table class="table table-bordered">
<tr><th>描述</th><th>负责成员</th><th>起始时间</th><th>结束时间</th></tr>
<?php foreach($affairs as $a): ?>
<tr>
  <td><?= htmlspecialchars($a['description']); ?></td>
  <td><?= htmlspecialchars($a['members']); ?></td>
  <td><?= htmlspecialchars($a['start_time']); ?></td>
  <td><?= htmlspecialchars($a['end_time']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<br>
 <h4><b>新增工作量</b></h4>
 <h5><span style="color:red">请注意：此处申报的工作必须有很细的颗粒度，不可以是"做研究"等长时/属于自己的任务，时长不可超过6天，多次跑腿/多次开会请分次申报！</span></h5>
 <form method="post" class="mt-3" id="taskForm">
   <input type="hidden" name="action" value="add">
   <div class="mb-3">
     <label class="form-label">工作事务描述(例如跑腿、开会、出差、临时材料等几天完成的紧急/具体事务)</label>
     <textarea name="description" class="form-control" rows="2" required></textarea>
   </div>
   <div class="mb-3">
     <label class="form-label">起始时间（请诚信填写，时长与工资挂钩）</label>
     <input type="datetime-local" name="start_time" id="startTime" class="form-control" required>
   </div>
   <div class="mb-3">
     <label class="form-label">结束时间（请诚信填写，时长与工资挂钩）</label>
     <input type="datetime-local" name="end_time" id="endTime" class="form-control" required>
     <div id="timeWarning" class="text-danger mt-2" style="display:none;">请确认您所选择的任务时长不超过6天，超过6天的任务请切分填写！（注意此处任务需保持较细颗粒度，便于考核）</div>
   </div>
   <button type="submit" class="btn btn-primary">申报该工作量</button>
 </form>
 <script>
 const startInput = document.getElementById('startTime');
 const endInput = document.getElementById('endTime');
 const warning = document.getElementById('timeWarning');
 const form = document.getElementById('taskForm');
 function checkInterval(){
   const start = new Date(startInput.value);
   const end = new Date(endInput.value);
   if(startInput.value && endInput.value){
     const diff = (end - start) / (1000 * 60 * 60 * 24);
     if(diff <= 0){
       warning.textContent = '结束时间必须晚于起始时间';
       warning.style.display = 'block';
       endInput.value = '';
       return false;
     } else if(diff > 6){
       warning.textContent = '请确认您所选择的任务时长不超过6天，超过6天的任务请切分填写！（注意此处任务需保持较细颗粒度，便于考核）';
       warning.style.display = 'block';
       endInput.value = '';
       return false;
     } else {
       warning.style.display = 'none';
     }
   }
   return true;
 }
 startInput.addEventListener('change', checkInterval);
 endInput.addEventListener('change', checkInterval);
 form.addEventListener('submit', function(e){
   if(!checkInterval()){
     e.preventDefault();
   }
 });
 </script>
<?php endif; ?>
</body>
</html>
