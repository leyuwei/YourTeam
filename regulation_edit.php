<?php
include 'auth_manager.php';
$id = (int)($_GET['id'] ?? 0);
if($id){
    $stmt = $pdo->prepare('SELECT * FROM regulations WHERE id=?');
    $stmt->execute([$id]);
    $reg = $stmt->fetch();
    if(!$reg){
        include 'header.php';
        echo '<div class="alert alert-danger">Regulation not found</div>';
        include 'footer.php';
        exit;
    }
    $files_stmt = $pdo->prepare('SELECT * FROM regulation_files WHERE regulation_id=?');
    $files_stmt->execute([$id]);
    $files = $files_stmt->fetchAll();
}else{
    $reg = ['category'=>'','description'=>''];
    $files = [];
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if($category!=='' && $description!==''){
        if($id){
            $stmt = $pdo->prepare('UPDATE regulations SET category=?, description=?, updated_at=CURDATE() WHERE id=?');
            $stmt->execute([$category,$description,$id]);
        }else{
            $stmt = $pdo->prepare('INSERT INTO regulations (category, description, updated_at, sort_order) VALUES (?,?,CURDATE(),(SELECT COALESCE(MAX(sort_order),0)+1 FROM regulations))');
            $stmt->execute([$category,$description]);
            $id = $pdo->lastInsertId();
        }
        if(!empty($_FILES['attachments']['name'][0])){
            $dir = __DIR__.'/regulation_uploads/'.$id;
            if(!is_dir($dir)) mkdir($dir,0777,true);
            foreach($_FILES['attachments']['name'] as $idx=>$orig){
                if($_FILES['attachments']['error'][$idx]===UPLOAD_ERR_OK){
                    $stored = uniqid().'-'.preg_replace('/[^A-Za-z0-9_.-]/','_',$orig);
                    move_uploaded_file($_FILES['attachments']['tmp_name'][$idx], $dir.'/'.$stored);
                    $pdo->prepare('INSERT INTO regulation_files (regulation_id, original_filename, stored_filename) VALUES (?,?,?)')->execute([$id,$orig,$stored]);
                }
            }
        }
        header('Location: notifications.php');
        exit;
    }
}
include 'header.php';
?>
<h2 data-i18n="<?php echo $id? 'regulation_edit.title_edit':'regulation_edit.title_add'; ?>"><?php echo $id? 'Edit Regulation':'Add Regulation'; ?></h2>
<form method="post" enctype="multipart/form-data">
  <div class="mb-3">
    <label class="form-label" data-i18n="regulation_edit.label_category">Category</label>
    <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($reg['category']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="regulation_edit.label_description">Description</label>
    <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($reg['description']); ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="regulation_edit.label_files">Attachments</label>
    <input type="file" name="attachments[]" class="form-control" multiple>
    <?php if($files): ?>
    <ul class="list-group mt-2">
      <?php foreach($files as $f): ?>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <a href="regulation_file.php?id=<?= $f['id']; ?>" target="_blank"><?= htmlspecialchars($f['original_filename']); ?></a>
        <a class="btn btn-sm btn-danger" href="regulation_file_delete.php?id=<?= $f['id']; ?>&reg_id=<?= $id; ?>" data-i18n="regulations.file_delete">Delete</a>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
  <button type="submit" class="btn btn-primary" data-i18n="regulation_edit.save">Save</button>
  <a class="btn btn-secondary" href="notifications.php" data-i18n="regulation_edit.cancel">Cancel</a>
</form>
<?php include 'footer.php'; ?>
