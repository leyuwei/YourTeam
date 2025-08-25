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
$error = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $errors = [];
    if($category === '' || $description === ''){
        $errors[] = 'Category and description are required';
    }
    if(!$errors){
        if($id){
            $stmt = $pdo->prepare('UPDATE regulations SET category=?, description=?, updated_at=CURDATE() WHERE id=?');
            $stmt->execute([$category,$description,$id]);
        }else{
            $sort = $pdo->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM regulations')->fetchColumn();
            $stmt = $pdo->prepare('INSERT INTO regulations (category, description, updated_at, sort_order) VALUES (?,?,CURDATE(),?)');
            $stmt->execute([$category,$description,$sort]);
            $id = $pdo->lastInsertId();
        }
        if(isset($_FILES['attachments']['name']) && $_FILES['attachments']['name'][0] !== ''){
            $dir = __DIR__.'/regulation_uploads/'.$id;
            if(!is_dir($dir)) mkdir($dir,0777,true);
            foreach($_FILES['attachments']['name'] as $idx=>$orig){
                $err = $_FILES['attachments']['error'][$idx];
                if($err === UPLOAD_ERR_OK){
                    $stored = uniqid().'-'.preg_replace('/[^A-Za-z0-9_.-]/','_',$orig);
                    if(!move_uploaded_file($_FILES['attachments']['tmp_name'][$idx], $dir.'/'.$stored)){
                        $errors[] = 'Failed to save file: '.$orig;
                    } else {
                        $pdo->prepare('INSERT INTO regulation_files (regulation_id, original_filename, stored_filename) VALUES (?,?,?)')->execute([$id,$orig,$stored]);
                    }
                }elseif($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE){
                    $errors[] = 'File too large: '.$orig;
                }elseif($err !== UPLOAD_ERR_NO_FILE){
                    $errors[] = 'Upload error: '.$orig;
                }
            }
        }
    }
    if(!$errors){
        if($isAjax){
            echo json_encode(['success'=>true]);
            exit;
        }else{
            header('Location: notifications.php');
            exit;
        }
    }else{
        if($isAjax){
            echo json_encode(['success'=>false,'error'=>implode("; ",$errors)]);
            exit;
        }else{
            $error = implode("; ",$errors);
        }
    }
}
include 'header.php';
?>
<h2 data-i18n="<?php echo $id? 'regulation_edit.title_edit':'regulation_edit.title_add'; ?>"><?php echo $id? 'Edit Regulation':'Add Regulation'; ?></h2>

<?php if(!empty($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
<?php endif; ?>
<form id="regForm" method="post" enctype="multipart/form-data">
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
  <div class="progress mt-2 d-none" id="uploadProgress">
    <div class="progress-bar" role="progressbar" style="width:0%"></div>
  </div>
  <div class="alert alert-danger mt-2 d-none" id="uploadError"></div>
  <button type="submit" class="btn btn-primary" data-i18n="regulation_edit.save">Save</button>
  <a class="btn btn-secondary" href="notifications.php" data-i18n="regulation_edit.cancel">Cancel</a>
</form>
<script>
window.addEventListener('load', function(){
  const form = document.getElementById('regForm');
  if(!form) return;
  const progress = document.getElementById('uploadProgress');
  const bar = progress.querySelector('.progress-bar');
  const errBox = document.getElementById('uploadError');
  form.addEventListener('submit', function(e){
    e.preventDefault();
    const xhr = new XMLHttpRequest();
    xhr.open('POST', form.action);
    xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
    const lang = localStorage.getItem('lang') || 'zh';
    const t = translations?.[lang] || {};
    progress.classList.remove('d-none');
    errBox.classList.add('d-none');
    bar.style.width = '0%';
    xhr.upload.onprogress = function(ev){
      if(ev.lengthComputable){
        bar.style.width = (ev.loaded / ev.total * 100) + '%';
      }
    };
    xhr.onload = function(){
      if(xhr.status === 200){
        try{
          const res = JSON.parse(xhr.responseText);
          if(res.success){
            window.location = 'notifications.php';
          }else{
            errBox.textContent = (t['regulation_edit.upload_error'] || 'Upload failed: ') + (res.error || '');
            errBox.classList.remove('d-none');
            progress.classList.add('d-none');
          }
        }catch(e){
          errBox.textContent = t['regulation_edit.upload_error'] || 'Upload failed';
          errBox.classList.remove('d-none');
          progress.classList.add('d-none');
        }
      }else{
        errBox.textContent = t['regulation_edit.upload_error'] || 'Upload failed';
        errBox.classList.remove('d-none');
        progress.classList.add('d-none');
      }
    };
    xhr.send(new FormData(form));
  });
});
</script>
<?php include 'footer.php'; ?>
