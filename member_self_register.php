<?php
require 'config.php';
require_once 'member_extra_helpers.php';
$error = '';
$msg = '';
$extraAttributes = getMemberExtraAttributes($pdo);
if(isset($_POST['action']) && $_POST['action'] === 'register'){
    $campus_id = trim($_POST['campus_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']) ?: null;
    $identity_number = trim($_POST['identity_number']) ?: null;
    $year_of_join = trim($_POST['year_of_join']);
    $year_of_join = $year_of_join === '' ? null : intval($year_of_join);
    $current_degree = trim($_POST['current_degree']) ?: null;
    $degree_pursuing = trim($_POST['degree_pursuing']) ?: null;
    $phone = trim($_POST['phone']) ?: null;
    $wechat = trim($_POST['wechat']) ?: null;
    $department = trim($_POST['department']) ?: null;
    $workplace = trim($_POST['workplace']) ?: null;
    $homeplace = trim($_POST['homeplace']) ?: null;
    try {
        $orderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order),-1)+1 FROM members');
        $nextOrder = $orderStmt->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO members(campus_id,name,email,identity_number,year_of_join,current_degree,degree_pursuing,phone,wechat,department,workplace,homeplace,status,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$campus_id,$name,$email,$identity_number,$year_of_join,$current_degree,$degree_pursuing,$phone,$wechat,$department,$workplace,$homeplace,'in_work',$nextOrder]);
        $newMemberId = (int)$pdo->lastInsertId();
        ensureMemberExtraValues($pdo, $newMemberId, [], $extraAttributes);
        $msg = '注册成功。';
    } catch (Exception $e) {
        $error = '注册失败，请检查输入后再试。';
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>新成员注册</title>
<link href="./style/bootstrap.min.css" rel="stylesheet">
<style>
  .container { max-width: 80%; }
</style>
</head>
<body class="container py-5">
<h2>新成员注册</h2>
<?php if($msg): ?><div class="alert alert-success mt-3"><?= $msg; ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger mt-3"><?= $error; ?></div><?php endif; ?>
<form method="post" class="mt-4">
  <input type="hidden" name="action" value="register">
  <div class="mb-3">
    <label class="form-label">一卡通号（9位）</label>
    <input type="text" name="campus_id" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">姓名</label>
    <input type="text" name="name" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">正式邮箱（学校/单位）</label>
    <input type="email" name="email" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">身份证号</label>
    <input type="text" name="identity_number" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">入学年份</label>
    <input type="number" name="year_of_join" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">已获学位</label>
    <input type="text" name="current_degree" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">当前学历</label>
    <input type="text" name="degree_pursuing" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">手机号</label>
    <input type="text" name="phone" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">微信号</label>
    <input type="text" name="wechat" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">所处学院/单位</label>
    <input type="text" name="department" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">学习/工作地点</label>
    <input type="text" name="workplace" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">家庭地址</label>
    <input type="text" name="homeplace" class="form-control" required>
  </div>
  <button type="submit" id="submitBtn" class="btn btn-primary" disabled>提交信息</button>
</form>
<script>
  const form = document.querySelector('form');
  const submitBtn = document.getElementById('submitBtn');
  function checkRequired(){
    const allFilled = Array.from(form.querySelectorAll('input[required]')).every(i => i.value.trim() !== '');
    submitBtn.disabled = !allFilled;
  }
  form.querySelectorAll('input[required]').forEach(i => i.addEventListener('input', checkRequired));
  checkRequired();
</script>
</body>
</html>
