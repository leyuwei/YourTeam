<?php
include 'header.php';
$id = $_GET['id'] ?? null;
$direction = ['title'=>'','description'=>'','bg_color'=>'#ffffff'];
if($id){
    $stmt = $pdo->prepare('SELECT * FROM research_directions WHERE id=?');
    $stmt->execute([$id]);
    $direction = $stmt->fetch();
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $title = $_POST['title'];
    $description = $_POST['description'];
    $bg_color = $_POST['bg_color'];
    if($id){
        $stmt = $pdo->prepare('UPDATE research_directions SET title=?, description=?, bg_color=? WHERE id=?');
        $stmt->execute([$title,$description,$bg_color,$id]);
    } else {
        $orderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order),-1)+1 FROM research_directions');
        $nextOrder = $orderStmt->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO research_directions(title,description,bg_color,sort_order) VALUES (?,?,?,?)');
        $stmt->execute([$title,$description,$bg_color,$nextOrder]);
    }
    header('Location: directions.php');
    exit();
}
?>
<h2><?= $id? 'Edit':'Add'; ?> 研究方向</h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label">方向题目</label>
    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($direction['title']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">方向具体描述</label>
    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($direction['description']); ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">背景颜色</label>
    <input type="color" name="bg_color" class="form-control form-control-color" value="<?= htmlspecialchars($direction['bg_color'] ?? '#ffffff'); ?>">
    <div class="mt-2">
      <?php
      $suggestedColors = ['#f1f9f7','#fffffa','#ffffff','#f1f5f9','#fbf4f6'];
      foreach ($suggestedColors as $color) {
          echo "<button type=\"button\" class=\"btn btn-sm border me-1\" style=\"background-color:$color;\" title=\"$color\" onclick=\"document.querySelector('input[name=bg_color]').value='$color'\"></button>";
      }
      ?>
    </div>
  </div>
  <button type="submit" class="btn btn-primary">保存</button>
  <a href="directions.php" class="btn btn-secondary">取消</a>
</form>
<?php include 'footer.php'; ?>
