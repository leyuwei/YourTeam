<?php

header("Content-Type:application/json; charset=utf-8");

include 'connection.php';

$query_statement = "DELETE FROM records WHERE id=" . $_POST["id"] . ";";
$db_query = $conn -> query($query_statement);

$path = "/var/www/html/uploads/";
$fn = $path . $_POST["filename"];

if ($db_query) {
    if (empty($_POST["filename"])) {
        echo "{\"result\": \"1\", \"content\": \"撤销发票成功！\"}";
    } else {
        if (unlink($fn)) {
            echo "{\"result\": \"1\", \"content\": \"撤销并删除发票成功！\"}";
        } else {
            echo "{\"result\": \"0\", \"content\": \"撤销并删除发票过程出现错误，请重试\"}";
        }
    }
} else {
    echo "{\"result\": \"0\", \"content\": \"撤销发票过程出现错误，请重试\"}";
}


?>