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

$query_statement = "UPDATE records SET batch=batch+1 WHERE id=" . $_POST['id'] . ";";
$db_query = $conn -> query($query_statement);

if ($db_query) {
    echo "{\"result\": \"1\", \"content\": \"发票类型更改成功\"}";
} else {
    echo "{\"result\": \"0\", \"content\": \"发票类型更改时出现错误\"}";
}


?>