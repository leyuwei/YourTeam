<?php

header("Content-Type:application/json; charset=utf-8");

include 'connection.php';

$query_statement = "SELECT * FROM roll WHERE username='" . $_POST["username"] . "' AND userid='" . $_POST["usernumber"] . "';";
$db_query = $conn -> query($query_statement);
if ($db_query) {
    if(mysqli_num_rows($db_query) == 0) {
        die("{\"result\": \"0\", \"content\": \"非法操作！您的账号错误或暂未被授权\"}");
    }
    while ($row = mysqli_fetch_row($db_query)) {
        if ($row[3] == '-1' || $row[3] == '0') {// 这里原版为[0]['permission']但是似乎不同版本的php语法有变化！以后遇到问题记得调整回去看看
            die("{\"result\": \"0\", \"content\": \"非法操作！您的账号错误或暂未被授权\"}");
        }
    }
} else {
    die("{\"result\": \"0\", \"content\": \"非法操作！您的账号错误或暂未被授权\"}");
}

$batch = isset($_POST["batch"]) ? $_POST["batch"] : 1;
$query_statement = "SELECT * FROM records WHERE isfinished=0 AND batch=" . $batch . " AND (filetype REGEXP '" . $_POST['filetype'] . "') ORDER BY username;";
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