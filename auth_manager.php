<?php
require_once 'auth.php';
if($_SESSION['role'] !== 'manager'){
    header('Location: index.php');
    exit();
}
?>
