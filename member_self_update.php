<?php
require 'config.php';
require_once 'member_attribute_helpers.php';
$member_id = $_SESSION['self_update_member_id'] ?? null;
$member = null;
$error = '';
$msg = '';
$current_projects = [];
$current_directions = [];
$customAttributes = fetch_member_attributes($pdo);
$attributeValues = [];

if(isset($_POST['action']) && $_POST['action'] === 'verify'){
    $name = $_POST['name'];
    $identity = $_POST['identity_number'];
    $stmt = $pdo->prepare('SELECT * FROM members WHERE name=? AND identity_number=?');
    $stmt->execute([$name, $identity]);
    $member = $stmt->fetch();
    if($member){
        $_SESSION['self_update_member_id'] = $member['id'];
        $member_id = $member['id'];
        assign_defaults_to_member($pdo, $member_id);
        $attributeValues = fetch_member_attribute_map($pdo, [$member_id]);
        $attributeValues = $attributeValues[$member_id] ?? [];
    } else {
        $error = '输入信息校验失败，请检查并重新提交验证.';
    }
}

if($member_id){
    if(!$member){
        $stmt = $pdo->prepare('SELECT * FROM members WHERE id=?');
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();
        assign_defaults_to_member($pdo, $member_id);
        $attributeValues = fetch_member_attribute_map($pdo, [$member_id]);
        $attributeValues = $attributeValues[$member_id] ?? [];
    }
    if(isset($_POST['action']) && $_POST['action'] === 'update'){
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
        $stmt = $pdo->prepare('UPDATE members SET campus_id=?, name=?, email=?, identity_number=?, year_of_join=?, current_degree=?, degree_pursuing=?, phone=?, wechat=?, department=?, workplace=?, homeplace=? WHERE id=?');
        $stmt->execute([$campus_id,$name,$email,$identity_number,$year_of_join,$current_degree,$degree_pursuing,$phone,$wechat,$department,$workplace,$homeplace,$member_id]);
        $attrPost = $_POST['attributes'] ?? [];
        if (!is_array($attrPost)) {
            $attrPost = [];
        }
        upsert_member_attribute_values($pdo, $member_id, $attrPost);
        $msg = 'Information updated successfully.';
        $stmt = $pdo->prepare('SELECT * FROM members WHERE id=?');
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();
        $attributeValues = fetch_member_attribute_map($pdo, [$member_id]);
        $attributeValues = $attributeValues[$member_id] ?? [];
    }
    $projStmt = $pdo->prepare('SELECT p.title FROM project_member_log l JOIN projects p ON l.project_id=p.id WHERE l.member_id=? AND l.exit_time IS NULL ORDER BY l.sort_order');
    $projStmt->execute([$member_id]);
    $current_projects = $projStmt->fetchAll(PDO::FETCH_COLUMN);
    $dirStmt = $pdo->prepare('SELECT d.title FROM direction_members dm JOIN research_directions d ON dm.direction_id=d.id WHERE dm.member_id=? ORDER BY dm.sort_order');
    $dirStmt->execute([$member_id]);
    $current_directions = $dirStmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>团队成员信息更新</title>
<link href="./style/bootstrap.min.css" rel="stylesheet">
<style>
  .container { max-width: 80%; }
</style>
</head>
<body class="container py-5">
<h2>团队成员信息更新</h2>
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
  <?php if($error): ?><div class="text-danger mb-3"><?= $error; ?></div><?php endif; ?>
  <button type="submit" class="btn btn-primary">验证身份</button>
  <a href="member_self_register.php" class="btn btn-secondary ms-2">新成员注册</a>
</form>
<?php else: ?>
<?php if($msg): ?><div class="alert alert-success mt-3"><?= $msg; ?></div><?php endif; ?>
<form method="post" class="mt-4">
  <input type="hidden" name="action" value="update">
  <div class="mb-3">
    <label class="form-label">一卡通号（9位）</label>
    <input type="text" name="campus_id" class="form-control" value="<?= htmlspecialchars($member['campus_id']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">姓名</label>
    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($member['name']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">正式邮箱（学校/单位）</label>
    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($member['email']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">身份证号</label>
    <input type="text" name="identity_number" class="form-control" value="<?= htmlspecialchars($member['identity_number']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">入学年份</label>
    <input type="number" name="year_of_join" class="form-control" value="<?= htmlspecialchars($member['year_of_join']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">已获学位</label>
    <input type="text" name="current_degree" class="form-control" value="<?= htmlspecialchars($member['current_degree']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">当前学历</label>
    <input type="text" name="degree_pursuing" class="form-control" value="<?= htmlspecialchars($member['degree_pursuing']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">手机号</label>
    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($member['phone']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">微信号</label>
    <input type="text" name="wechat" class="form-control" value="<?= htmlspecialchars($member['wechat']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">所处学院/单位</label>
    <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($member['department']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">学习/工作地点</label>
    <input type="text" name="workplace" class="form-control" value="<?= htmlspecialchars($member['workplace']); ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">家庭地址</label>
    <input type="text" name="homeplace" class="form-control" value="<?= htmlspecialchars($member['homeplace']); ?>">
  </div>
  <?php if($customAttributes): ?>
  <hr>
  <h4 class="mt-4">自定义属性</h4>
  <?php foreach($customAttributes as $attr):
    $attrId = (int)$attr['id'];
    $attrValue = $attributeValues[$attrId] ?? $attr['default_value'];
  ?>
  <div class="mb-3">
    <label class="form-label">
      <?= htmlspecialchars($attr['label_zh']); ?>
      <?php if(trim((string)$attr['label_en']) !== ''): ?>
        <small class="text-muted ms-2"><?= htmlspecialchars($attr['label_en']); ?></small>
      <?php endif; ?>
    </label>
    <input type="text" name="attributes[<?= $attrId; ?>]" class="form-control" value="<?= htmlspecialchars((string)($attrValue ?? '')); ?>">
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
  <button type="submit" class="btn btn-primary">更新信息</button>
</form>
<h4 class="mt-5">当前参与/承担的项目</h4>
<ul>
  <?php if($current_projects): foreach($current_projects as $p): ?>
    <li><?= htmlspecialchars($p); ?></li>
  <?php endforeach; else: ?>
    <li><em>暂无</em></li>
  <?php endif; ?>
</ul>
<h4>您的研究方向</h4>
<ul>
  <?php if($current_directions): foreach($current_directions as $d): ?>
    <li><?= htmlspecialchars($d); ?></li>
  <?php endforeach; else: ?>
    <li><em>暂无</em></li>
  <?php endif; ?>
</ul>
<?php endif; ?>
</body>
</html>
