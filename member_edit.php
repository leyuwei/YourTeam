<?php
include 'header.php';
$id = $_GET['id'] ?? null;
$member = ['campus_id'=>'','name'=>'','email'=>'','identity_number'=>'','year_of_join'=>'','current_degree'=>'','degree_pursuing'=>'','phone'=>'','wechat'=>'','department'=>'','workplace'=>'','homeplace'=>'','status'=>'in_work'];
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
    $status = $_POST['status'] ?? 'in_work';
    if($id){
        $stmt = $pdo->prepare('UPDATE members SET campus_id=?, name=?, email=?, identity_number=?, year_of_join=?, current_degree=?, degree_pursuing=?, phone=?, wechat=?, department=?, workplace=?, homeplace=?, status=? WHERE id=?');
        $stmt->execute([$campus_id,$name,$email,$identity_number,$year_of_join,$current_degree,$degree_pursuing,$phone,$wechat,$department,$workplace,$homeplace,$status,$id]);
    } else {
        $orderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order),-1)+1 FROM members');
        $nextOrder = $orderStmt->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO members(campus_id,name,email,identity_number,year_of_join,current_degree,degree_pursuing,phone,wechat,department,workplace,homeplace,status,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$campus_id,$name,$email,$identity_number,$year_of_join,$current_degree,$degree_pursuing,$phone,$wechat,$department,$workplace,$homeplace,$status,$nextOrder]);
    }
    header('Location: members.php');
    exit();
}
?>
<h2 data-i18n="<?php echo $id? 'member_edit.title_edit':'member_edit.title_add'; ?>">
  <?php echo $id? 'Edit Member':'Add Member'; ?>
</h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label" data-i18n="members.table.campus_id">Campus ID</label>
    <input type="text" name="campus_id" class="form-control" value="<?php echo htmlspecialchars($member['campus_id']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="members.table.name">Name</label>
    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($member['name']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="members.table.email">Email</label>
    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($member['email']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="members.table.identity_number">Identity Number</label>
    <input type="text" name="identity_number" class="form-control" value="<?php echo htmlspecialchars($member['identity_number']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="members.table.year_of_join">Year of Join</label>
    <input type="number" name="year_of_join" class="form-control" value="<?php echo htmlspecialchars($member['year_of_join']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="members.table.current_degree">Current Degree</label>
    <input type="text" name="current_degree" class="form-control" value="<?php echo htmlspecialchars($member['current_degree']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="members.table.degree_pursuing">Degree Pursuing</label>
    <input type="text" name="degree_pursuing" class="form-control" value="<?php echo htmlspecialchars($member['degree_pursuing']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="members.table.phone">Phone</label>
    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($member['phone']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="members.table.wechat">WeChat</label>
    <input type="text" name="wechat" class="form-control" value="<?php echo htmlspecialchars($member['wechat']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="members.table.department">Department</label>
    <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($member['department']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="members.table.workplace">Workplace</label>
    <input type="text" name="workplace" class="form-control" value="<?php echo htmlspecialchars($member['workplace']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="members.table.homeplace">Homeplace</label>
    <input type="text" name="homeplace" class="form-control" value="<?php echo htmlspecialchars($member['homeplace']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="members.table.status">Status</label>
    <select name="status" class="form-select">
      <option value="in_work" <?php echo $member['status']==='in_work'?'selected':''; ?> data-i18n="members.status.in_work">In Work</option>
      <option value="exited" <?php echo $member['status']==='exited'?'selected':''; ?> data-i18n="members.status.exited">Exited</option>
    </select>
  </div>
  <button type="submit" class="btn btn-primary" data-i18n="member_edit.save">Save</button>
  <a href="members.php" class="btn btn-secondary" data-i18n="member_edit.cancel">Cancel</a>
</form>
<?php include 'footer.php'; ?>
