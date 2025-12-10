<?php
include 'auth_manager.php';

// Preserve legacy entrypoints by redirecting to the unified modal page.
$id = $_GET['id'] ?? '';
$target = 'notifications.php';
if($id !== ''){
    $target .= '?edit='.urlencode($id);
}
header('Location: '.$target);
exit();
