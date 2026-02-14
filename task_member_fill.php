<?php
// Public endpoint for task members to report their work without login
// Only basic configuration and DB connection are required
require 'config.php';
$task_id = $_GET['task_id'] ?? null;
if(!$task_id){
    echo 'Invalid task id';
    exit();
}

$taskStmt = $pdo->prepare('SELECT title,status FROM tasks WHERE id=?');
$taskStmt->execute([$task_id]);
$taskRow = $taskStmt->fetch(PDO::FETCH_ASSOC);
if (!$taskRow) { echo 'Task not found'; exit(); }
$taskTitle = $taskRow['title'];
$taskStatus = $taskRow['status'];

$isManager = ($_SESSION['role'] ?? '') === 'manager';
$loggedMemberId = ($_SESSION['role'] ?? '') === 'member' ? ($_SESSION['member_id'] ?? null) : null;

if ($loggedMemberId) {
    $_SESSION['fill_member_id'] = $loggedMemberId;
    $_SESSION['fill_task_id'] = $task_id;
}
if(isset($_SESSION['fill_task_id']) && $_SESSION['fill_task_id'] != $task_id){
    unset($_SESSION['fill_task_id'], $_SESSION['fill_member_id']);
}
$member_id = $_SESSION['fill_member_id'] ?? $loggedMemberId;
$error = '';
$msg = '';
if(empty($_SESSION['fill_submit_token'])){
    $_SESSION['fill_submit_token'] = bin2hex(random_bytes(16));
}

$canModify = $isManager || $taskStatus === 'active';
if(!$canModify && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $error = '该任务已暂停或结束，无法继续申报。';
}

if(!$member_id && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify'){
    if(!$canModify){
        $error = '该任务已暂停或结束，无法继续申报。';
    } else {
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
}
if($member_id && $canModify && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add'){
    $description = trim($_POST['description'] ?? '');
    $start_date = $_POST['start_time'] ?? '';
    $end_date = $_POST['end_time'] ?? '';
    $submitToken = $_POST['submit_token'] ?? '';
    if(!$submitToken || !hash_equals($_SESSION['fill_submit_token'], $submitToken)){
        $error = '请勿重复提交，请刷新页面后重试。';
    } elseif($description === ''){
        $error = '事务描述不能为空';
    } elseif(!$start_date || !$end_date){
        $error = '请填写完整的起止日期';
    } elseif(strtotime($end_date) < strtotime($start_date)){
        $error = '结束日期必须不早于起始日期';
    } else {
        $start_time = $start_date . ' 00:00:00';
        $end_time = date('Y-m-d 00:00:00', strtotime($end_date . ' +1 day'));
        $existingStmt = $pdo->prepare('SELECT a.id FROM task_affairs a INNER JOIN task_affair_members am ON am.affair_id = a.id WHERE a.task_id=? AND a.description=? AND a.start_time=? AND a.end_time=? AND am.member_id=? LIMIT 1');
        $existingStmt->execute([$task_id, $description, $start_time, $end_time, $member_id]);
        if($existingStmt->fetch()){
            $error = '检测到重复申报：同一成员相同时间与描述的事务已存在。';
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO task_affairs(task_id,description,start_time,end_time,status) VALUES (?,?,?,?,?)');
                $stmt->execute([$task_id,$description,$start_time,$end_time,'pending']);
                $affair_id = $pdo->lastInsertId();
                $pdo->prepare('INSERT INTO task_affair_members(affair_id,member_id) VALUES (?,?)')->execute([$affair_id,$member_id]);
                $pdo->commit();
                $_SESSION['fill_submit_token'] = bin2hex(random_bytes(16));
                header('Location: task_member_fill.php?task_id=' . urlencode((string)$task_id) . '&result=added');
                exit();
            } catch (Throwable $e) {
                if($pdo->inTransaction()){
                    $pdo->rollBack();
                }
                $error = '提交失败，请稍后重试';
            }
        }
    }
}
if($member_id && $canModify && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join'){
    $affair_id = $_POST['affair_id'];
    $check = $pdo->prepare('SELECT 1 FROM task_affair_members WHERE affair_id=? AND member_id=?');
    $check->execute([$affair_id,$member_id]);
    if(!$check->fetch()){
        $pdo->prepare('INSERT INTO task_affair_members(affair_id,member_id) VALUES (?,?)')->execute([$affair_id,$member_id]);
        $msg = '已加入该事务';
    }
}
if($member_id && $canModify && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'leave'){
    $affair_id = $_POST['affair_id'];
    $pdo->prepare('DELETE FROM task_affair_members WHERE affair_id=? AND member_id=?')->execute([$affair_id,$member_id]);
    $msg = '已撤回加入';
}
$affairs = [];
if($member_id){
    $stmt = $pdo->prepare('SELECT a.id,a.description,a.start_time,a.end_time,a.status,GROUP_CONCAT(m.name SEPARATOR ", ") AS members, GROUP_CONCAT(m.id) AS member_ids FROM task_affairs a LEFT JOIN task_affair_members am ON a.id=am.affair_id LEFT JOIN members m ON am.member_id=m.id WHERE a.task_id=? GROUP BY a.id ORDER BY a.start_time DESC');
    $stmt->execute([$task_id]);
    $affairs = $stmt->fetchAll();
}
if(isset($_GET['result']) && $_GET['result'] === 'added'){
    $msg = '已提交';
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>团队成员工作事务申报</title>
<link href="./style/bootstrap.min.css" rel="stylesheet">
<style>
  .container { max-width: 80%; }
</style>
</head>
<body class="container py-5">
<h2>团队</h2>
<h2>工作量报备 - 与绩效挂钩！</h2>
<h4><span style="color:red">您正在申报：<?= htmlspecialchars($taskTitle); ?>方面的工作</span></h4>
<p><strong>当前任务状态：</strong> <?= htmlspecialchars($taskStatus); ?></p>
<br>
<?php if(!$canModify): ?>
<div class="alert alert-warning">该任务目前已暂停或结束，暂不接受新的工作量申报。</div>
<?php elseif(!$member_id): ?>
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
<div class="alert alert-info">
  如需参与他人已申报的事务，请在下方列表中找到相应记录并点击“加入”按钮；仅在您实际参与该事务时使用此功能。
</div>
<h4><b>已填工作事务</b></h4>
<table class="table table-bordered">
<tr><th>描述</th><th>负责成员</th><th>起始日期</th><th>结束日期</th><th>天数</th><th>状态</th><th>操作</th></tr>
<?php foreach($affairs as $a): ?>
<?php $days = (strtotime($a['end_time']) - strtotime($a['start_time'])) / 86400; $joined = $a['member_ids'] ? in_array($member_id, explode(',', $a['member_ids'])) : false; $status_text = $a['status']==='confirmed' ? '已确认' : '待确认'; ?>
<tr>
  <td><?= htmlspecialchars($a['description']); ?></td>
  <td><?= htmlspecialchars($a['members']); ?></td>
  <td><?= htmlspecialchars(date('Y-m-d', strtotime($a['start_time']))); ?></td>
  <td><?= htmlspecialchars(date('Y-m-d', strtotime($a['end_time'] . ' -1 day'))); ?></td>
  <td><?= htmlspecialchars($days); ?></td>
  <td><?= $status_text; ?></td>
  <td>
    <?php if(!$joined && $canModify): ?>
    <form method="post" style="display:inline;">
      <input type="hidden" name="action" value="join">
      <input type="hidden" name="affair_id" value="<?= $a['id']; ?>">
      <button type="submit" class="btn btn-sm btn-success">加入</button>
    </form>
    <?php elseif($joined && $canModify): ?>
    <form method="post" style="display:inline;">
      <input type="hidden" name="action" value="leave">
      <input type="hidden" name="affair_id" value="<?= $a['id']; ?>">
      <button type="submit" class="btn btn-sm btn-warning">撤回</button>
    </form>
    <?php elseif($joined): ?>
    <span class="text-muted">已加入</span>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</table>
<br>
 <?php if($canModify): ?>
 <h4><b>新增工作量</b></h4>
 <div class="alert alert-danger">
   <ul class="mb-0">
     <li>以“天”为最小单位填写，不得超过6天，申报的工作不可以是"做自己研究"等长时/属于自己的任务，多次跑腿/多次开会请分次申报</li>
     <li>如一天中断断续续有工作，请直接填写1整天工作量(例如选择8月2日~8月2日)；如连续两天断断续续的细碎任务，请一次性填报2整天工作量(例如选择8月2日~8月3日)</li>
     <li>填报工作量和时长必须具体，如周一、周五各干一天活，则需分两次，每次填报一天任务，切勿一次性申报5天！管理员会定期清除不合理申报</li>
   </ul>
 </div>
 <form method="post" class="mt-3" id="taskForm">
   <input type="hidden" name="action" value="add">
   <input type="hidden" name="submit_token" value="<?= htmlspecialchars($_SESSION['fill_submit_token']); ?>">
   <div class="mb-3">
     <label class="form-label">工作事务描述(例如跑腿、开会、出差、临时材料等几天完成的紧急/具体事务)</label>
     <textarea name="description" class="form-control" rows="2" required></textarea>
   </div>
   <div class="mb-3">
     <label class="form-label">起始日期（请诚信填写，时长与工资挂钩）</label>
     <input type="date" name="start_time" id="startTime" class="form-control" required>
   </div>
   <div class="mb-3">
     <label class="form-label">结束日期（请诚信填写，时长与工资挂钩）</label>
     <input type="date" name="end_time" id="endTime" class="form-control" required>
     <div id="timeWarning" class="text-danger mt-2" style="display:none;"></div>
   <div id="dayCount" class="mt-2"></div>
 </div>
 <button type="submit" class="btn btn-primary" id="submitFillBtn">申报该工作量</button>
</form>
 <?php else: ?>
 <div class="alert alert-warning">该任务当前不开放新增工作量填报。</div>
 <?php endif; ?>
<script>
 const startInput = document.getElementById('startTime');
 const endInput = document.getElementById('endTime');
 const warning = document.getElementById('timeWarning');
 const dayCount = document.getElementById('dayCount');
 const form = document.getElementById('taskForm');
 const submitFillBtn = document.getElementById('submitFillBtn');
 if(form && startInput && endInput && warning && dayCount){
   function updateInfo(){
     if(startInput.value && endInput.value){
       const start = new Date(startInput.value);
       const end = new Date(endInput.value);
       let diff = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
       if(diff <= 0){
         warning.textContent = '结束日期必须不早于起始日期';
         warning.style.display = 'block';
         dayCount.textContent = '';
         endInput.value = '';
         return false;
       } else if(diff > 6){
         warning.textContent = '请确认您所选择的任务时长不超过6天，超过6天的任务请切分填写！（注意此处任务需保持较细颗粒度，便于考核）';
         warning.style.display = 'block';
         dayCount.textContent = '';
         endInput.value = '';
         return false;
       } else {
         warning.style.display = 'none';
         dayCount.textContent = `本次申报工作量：${diff} 天`;
       }
     } else {
       warning.style.display = 'none';
       dayCount.textContent = '';
     }
     return true;
   }
   startInput.addEventListener('change', updateInfo);
   endInput.addEventListener('change', updateInfo);
   form.addEventListener('submit', function(e){
     if(!updateInfo()){
       e.preventDefault();
       return;
     }
     if(submitFillBtn){
       submitFillBtn.disabled = true;
       submitFillBtn.textContent = '提交中...';
     }
   });
 }
 </script>
<?php endif; ?>
<script src="team_name.js"></script>
</body>
</html>
