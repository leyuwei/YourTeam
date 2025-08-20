<?php
include 'auth_manager.php';
include 'header.php';
$id = $_GET['id'] ?? null;
$notification = ['content'=>'','valid_begin_date'=>'','valid_end_date'=>''];
$selected = [];
if($id){
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE id=?');
    $stmt->execute([$id]);
    $notification = $stmt->fetch();
    $stmt = $pdo->prepare('SELECT member_id FROM notification_targets WHERE notification_id=?');
    $stmt->execute([$id]);
    $selected = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
$members = $pdo->query('SELECT id,name FROM members ORDER BY name')->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){
    $content = $_POST['content'];
    $begin = $_POST['valid_begin_date'];
    $end = $_POST['valid_end_date'];
    $members_selected = $_POST['members'] ?? [];
    if($id){
        $stmt = $pdo->prepare('UPDATE notifications SET content=?, valid_begin_date=?, valid_end_date=? WHERE id=?');
        $stmt->execute([$content,$begin,$end,$id]);
        $pdo->prepare('DELETE FROM notification_targets WHERE notification_id=?')->execute([$id]);
        foreach($members_selected as $m){
            $pdo->prepare('INSERT INTO notification_targets(notification_id,member_id) VALUES(?,?)')->execute([$id,$m]);
        }
    } else {
        $stmt = $pdo->prepare('INSERT INTO notifications(content,valid_begin_date,valid_end_date) VALUES(?,?,?)');
        $stmt->execute([$content,$begin,$end]);
        $nid = $pdo->lastInsertId();
        foreach($members_selected as $m){
            $pdo->prepare('INSERT INTO notification_targets(notification_id,member_id) VALUES(?,?)')->execute([$nid,$m]);
        }
    }
    header('Location: notifications.php');
    exit();
}
?>
<h2 data-i18n="<?= $id? 'notification_edit.title_edit':'notification_edit.title_add'; ?>"><?= $id? 'Edit Notification':'Add Notification'; ?></h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label" data-i18n="notification_edit.label_content">Content</label>
    <textarea name="content" class="form-control" rows="4" required><?= htmlspecialchars($notification['content']); ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="notification_edit.label_begin">Begin Date</label>
    <input type="date" name="valid_begin_date" class="form-control" value="<?= htmlspecialchars($notification['valid_begin_date']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="notification_edit.label_end">End Date</label>
    <input type="date" name="valid_end_date" class="form-control" value="<?= htmlspecialchars($notification['valid_end_date']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="notification_edit.label_members">Target Members</label>
    <?php foreach($members as $m): ?>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="members[]" value="<?= $m['id']; ?>" <?= in_array($m['id'],$selected)?'checked':''; ?>>
      <label class="form-check-label"><?= htmlspecialchars($m['name']); ?></label>
    </div>
    <?php endforeach; ?>
  </div>
  <button type="submit" class="btn btn-primary" data-i18n="notification_edit.save">Save</button>
  <a href="notifications.php" class="btn btn-secondary" data-i18n="notification_edit.cancel">Cancel</a>
</form>
<?php include 'footer.php'; ?>
