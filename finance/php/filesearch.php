<?php

header("Content-Type:application/json; charset=utf-8");

include 'connection.php';

$query_statement = "SELECT * FROM roll WHERE username='" . $_POST["username"] . "' AND userid='" . $_POST["usernumber"] . "';";
$db_query = $conn -> query($query_statement);
if ($db_query) {
    if(mysqli_num_rows($db_query) == 0) {
        die("{\"result\": \"0\", \"content\": \"提交的姓名与一卡通号不在准予报销名单中，请检查所填信息或申诉！若您为新生，可能是后台数据库暂未录入，请联系本网站开发人员！\"}");
    }
} else {
    die("{\"result\": \"0\", \"content\": \"检索出现错误\"}");
}


$query_statement = "SELECT * FROM records WHERE username='" . $_POST["username"] . "' AND userid='" . $_POST["usernumber"] . "' ORDER BY isfinished, batch DESC, uploadtime DESC;";
$db_query = $conn -> query($query_statement);

if ($db_query) {
    $newArr = array();
    $newArr['content'] = array();
    while ($db_field = mysqli_fetch_assoc($db_query)) {
        $newArr['content'][] = $db_field;
    }
    $newArr['result'] = '1';
    echo json_encode($newArr);
} else {
    echo "{\"result\": \"0\", \"content\": \"检索出现错误\"}";
}


?>