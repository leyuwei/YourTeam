<?php
require_once 'config.php';
if(!isset($_SESSION['manager_id'])){
    header('Location: login.php');
    exit();
}
?>
