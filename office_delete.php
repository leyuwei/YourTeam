<?php
include 'auth_manager.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT layout_image FROM offices WHERE id = ?');
    $stmt->execute([$id]);
    $office = $stmt->fetch();
    if ($office) {
        $delete = $pdo->prepare('DELETE FROM offices WHERE id = ?');
        $delete->execute([$id]);
        if (!empty($office['layout_image'])) {
            $path = __DIR__ . '/' . $office['layout_image'];
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}

header('Location: offices.php');
exit();
