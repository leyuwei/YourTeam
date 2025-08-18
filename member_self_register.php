<?php
require 'config.php';
$error = '';
$msg = '';
if(isset($_POST['action']) && $_POST['action'] === 'register'){
    $campus_id = $_POST['campus_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $identity_number = $_POST['identity_number'];
    $year_of_join = $_POST['year_of_join'];
    $current_degree = $_POST['current_degree'];
    $degree_pursuing = $_POST['degree_pursuing'];
    $phone = $_POST['phone'];
    $wechat = $_POST['wechat'];
    $department = $_POST['department'];
    $workplace = $_POST['workplace'];
    $homeplace = $_POST['homeplace'];
    $orderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order),-1)+1 FROM members');
    $nextOrder = $orderStmt->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO members(campus_id,name,email,identity_number,year_of_join,current_degree,degree_pursuing,phone,wechat,department,workplace,homeplace,status,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$campus_id,$name,$email,$identity_number,$year_of_join,$current_degree,$degree_pursuing,$phone,$wechat,$department,$workplace,$homeplace,'in_work',$nextOrder]);
    $msg = '注册成功。';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>新成员注册</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .container { max-width: 80%; }
</style>
</head>
<body class="container py-5">
<h2>新成员注册</h2>
<?php if($msg): ?><div class="alert alert-success mt-3"><?= $msg; ?></div><?php endif; ?>
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
    <input type="email" name="email" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">身份证号</label>
    <input type="text" name="identity_number" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">入学年份</label>
    <input type="number" name="year_of_join" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">已获学位</label>
    <input type="text" name="current_degree" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">当前学历</label>
    <input type="text" name="degree_pursuing" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">手机号</label>
    <input type="text" name="phone" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">微信号</label>
    <input type="text" name="wechat" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">所处学院/单位</label>
    <input type="text" name="department" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">学习/工作地点</label>
    <input type="text" name="workplace" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">家庭地址</label>
    <input type="text" name="homeplace" class="form-control">
  </div>
  <button type="submit" class="btn btn-primary">提交信息</button>
</form>
</body>
</html>
