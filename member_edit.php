<?php
include 'header.php';
$id = $_GET['id'] ?? null;
$member = ['campus_id'=>'','name'=>'','email'=>'','identity_number'=>'','year_of_join'=>'','current_degree'=>'','degree_pursuing'=>'','phone'=>'','wechat'=>'','department'=>'','workplace'=>'','homeplace'=>''];
if($id){
    $stmt = $pdo->prepare('SELECT * FROM members WHERE id=?');
    $stmt->execute([$id]);
    $member = $stmt->fetch();
}
if($_SERVER['REQUEST_METHOD']==='POST'){
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
    if($id){
        $stmt = $pdo->prepare('UPDATE members SET campus_id=?, name=?, email=?, identity_number=?, year_of_join=?, current_degree=?, degree_pursuing=?, phone=?, wechat=?, department=?, workplace=?, homeplace=? WHERE id=?');
        $stmt->execute([$campus_id,$name,$email,$identity_number,$year_of_join,$current_degree,$degree_pursuing,$phone,$wechat,$department,$workplace,$homeplace,$id]);
    } else {
        $orderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order),-1)+1 FROM members');
        $nextOrder = $orderStmt->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO members(campus_id,name,email,identity_number,year_of_join,current_degree,degree_pursuing,phone,wechat,department,workplace,homeplace,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$campus_id,$name,$email,$identity_number,$year_of_join,$current_degree,$degree_pursuing,$phone,$wechat,$department,$workplace,$homeplace,$nextOrder]);
    }
    header('Location: members.php');
    exit();
}
?>
<h2><?php echo $id? 'Edit':'Add'; ?> 团队成员</h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label">一卡通号</label>
    <input type="text" name="campus_id" class="form-control" value="<?php echo htmlspecialchars($member['campus_id']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">姓名</label>
    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($member['name']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">正式邮箱</label>
    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($member['email']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">身份证号</label>
    <input type="text" name="identity_number" class="form-control" value="<?php echo htmlspecialchars($member['identity_number']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">入学年份</label>
    <input type="number" name="year_of_join" class="form-control" value="<?php echo htmlspecialchars($member['year_of_join']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">已获学位</label>
    <input type="text" name="current_degree" class="form-control" value="<?php echo htmlspecialchars($member['current_degree']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">当前学历</label>
    <input type="text" name="degree_pursuing" class="form-control" value="<?php echo htmlspecialchars($member['degree_pursuing']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">手机号</label>
    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($member['phone']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">微信号</label>
    <input type="text" name="wechat" class="form-control" value="<?php echo htmlspecialchars($member['wechat']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">所处学院/单位</label>
    <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($member['department']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">工作地点</label>
    <input type="text" name="workplace" class="form-control" value="<?php echo htmlspecialchars($member['workplace']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">家庭地址</label>
    <input type="text" name="homeplace" class="form-control" value="<?php echo htmlspecialchars($member['homeplace']); ?>">
  </div>
  <button type="submit" class="btn btn-primary">更新</button>
  <a href="members.php" class="btn btn-secondary">取消</a>
</form>
<?php include 'footer.php'; ?>
