<?php
include 'auth.php';

$id = $_GET['id'] ?? ($_POST['id'] ?? null);
$direction = ['title' => '', 'description' => '', 'bg_color' => '#ffffff'];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM research_directions WHERE id=?');
    $stmt->execute([$id]);
    $direction = $stmt->fetch() ?: $direction;
}

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$acceptsJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $bg_color = $_POST['bg_color'] ?? '#ffffff';

    if ($id) {
        $stmt = $pdo->prepare('UPDATE research_directions SET title=?, description=?, bg_color=? WHERE id=?');
        $stmt->execute([$title, $description, $bg_color, $id]);
    } else {
        $orderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order),-1)+1 FROM research_directions');
        $nextOrder = $orderStmt->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO research_directions(title,description,bg_color,sort_order) VALUES (?,?,?,?)');
        $stmt->execute([$title, $description, $bg_color, $nextOrder]);
        $id = $pdo->lastInsertId();
    }

    if ($isAjax || $acceptsJson) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'id' => $id]);
    } else {
        header('Location: directions.php');
    }
    exit();
}

if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'id' => $id,
        'title' => $direction['title'] ?? '',
        'description' => $direction['description'] ?? '',
        'bg_color' => $direction['bg_color'] ?? '#ffffff',
    ]);
    exit();
}

include 'header.php';
?>
<h2 data-i18n="<?= $id ? 'direction_edit.title_edit' : 'direction_edit.title_add'; ?>">
  <?= $id ? 'Edit Research Direction' : 'Add Research Direction'; ?>
</h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label" data-i18n="direction_edit.label_title">Direction Title</label>
    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($direction['title']); ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="direction_edit.label_description">Description</label>
    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($direction['description']); ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label" data-i18n="direction_edit.label_bg">Background Color</label>
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
  <button type="submit" class="btn btn-primary" data-i18n="direction_edit.save">Save</button>
  <a href="directions.php" class="btn btn-secondary" data-i18n="direction_edit.cancel">Cancel</a>
</form>
<?php include 'footer.php'; ?>
