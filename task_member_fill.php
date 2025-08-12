<?php
require 'config.php';
$task_id = $_GET['task_id'] ?? null;
if(!$task_id){
    echo 'Invalid task id';
    exit();
}
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
    $stmt = $pdo->prepare('INSERT INTO task_affairs(task_id,description,start_time,end_time) VALUES (?,?,?,?)');
    $stmt->execute([$task_id,$description,$start_time,$end_time]);
    $affair_id = $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO task_affair_members(affair_id,member_id) VALUES (?,?)')->execute([$affair_id,$member_id]);
    $msg = '已提交';
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
<title>紧急事务填写</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .container { max-width: 80%; }
</style>
</head>
<body class="container py-5">
<h2>紧急事务填写</h2>
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
  <button type="submit" class="btn btn-primary">验证</button>
</form>
<?php else: ?>
<?php if($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<h4>已有紧急事务</h4>
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
<h4>新增紧急事务</h4>
<form method="post" class="mt-3">
  <input type="hidden" name="action" value="add">
  <div class="mb-3">
    <label class="form-label">紧急事务描述</label>
    <textarea name="description" class="form-control" rows="2" required></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">起始时间</label>
    <input type="datetime-local" name="start_time" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">结束时间</label>
    <input type="datetime-local" name="end_time" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">提交</button>
</form>
<?php endif; ?>
</body>
</html>
