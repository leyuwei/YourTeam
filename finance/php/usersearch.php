<?php

header("Content-Type:application/json; charset=utf-8");

include 'connection.php';

$query_statement = "SELECT * FROM roll WHERE userid='" . $_POST["usernumber"] . "';";
$db_query = $conn -> query($query_statement);
if ($db_query) {
    if(mysqli_num_rows($db_query) == 0) {
        die("{\"result\": \"0\", \"content\": \"填写的姓名与一卡通号错误或不在准予报销名单中\"}");
    }
    $newArr = array();
    $newArr['content'] = array();
    while ($db_field = mysqli_fetch_assoc($db_query)) {
        $newArr['content'][] = $db_field;
    }
    $newArr['result'] = '1';
    echo json_encode($newArr);
} else {
    die("{\"result\": \"0\", \"content\": \"检索出现错误\"}");
}

?>