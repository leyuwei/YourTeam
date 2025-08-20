<?php

header("Content-Type:application/json; charset=utf-8");

include "connection.php";

if ($_POST["new_timelimit"]) {
    $query_statement = "SELECT * FROM roll WHERE username='" . $_POST["username"] . "' AND userid='" . $_POST["usernumber"] . "';";
    $db_query = $conn -> query($query_statement);
    if ($db_query) {
        if(mysqli_num_rows($db_query) == 0) {
            die("{\"result\": \"0\", \"content\": \"非法操作！您的账号错误或暂未被授权\"}");
        }
        while ($row = mysqli_fetch_row($db_query)) {
            if ($row[3] == '-1' || $row[3] == '0') {
                die("{\"result\": \"0\", \"content\": \"非法操作！您的账号错误或暂未被授权\"}");
            }
        }
    } else {
        die("{\"result\": \"0\", \"content\": \"非法操作！您的账号错误或暂未被授权\"}");
    }

    $query_statement = "UPDATE basecfg SET current_limit='" . $_POST['new_timelimit'] . "' where id=1;";
    $db_query = $conn -> query($query_statement);

    if ($db_query) {
        echo "{\"result\": \"1\", \"content\": \"新一轮报销截止时间设置成功\"}";
    } else {
        echo "{\"result\": \"0\", \"content\": \"报销截止时间设置出现错误\"}";
    }
} if ($_POST["new_pricelimit"]) {
    $query_statement = "SELECT * FROM roll WHERE username='" . $_POST["username"] . "' AND userid='" . $_POST["usernumber"] . "';";
    $db_query = $conn -> query($query_statement);
    if ($db_query) {
        if(mysqli_num_rows($db_query) == 0) {
            die("{\"result\": \"0\", \"content\": \"非法操作！您的账号错误或暂未被授权\"}");
        }
        while ($row = mysqli_fetch_row($db_query)) {
            if ($row[3] == '-1' || $row[3] == '0') {
                die("{\"result\": \"0\", \"content\": \"非法操作！您的账号错误或暂未被授权\"}");
            }
        }
    } else {
        die("{\"result\": \"0\", \"content\": \"非法操作！您的账号错误或暂未被授权\"}");
    }

    $query_statement = "UPDATE basecfg SET price_limit='" . $_POST['new_pricelimit'] . "' where id=1;";
    $db_query = $conn -> query($query_statement);

    if ($db_query) {
        echo "{\"result\": \"1\", \"content\": \"新的报销限额设置成功\"}";
    } else {
        echo "{\"result\": \"0\", \"content\": \"报销限额设置出现错误\"}";
    }
} else {
    $query_statement = "SELECT * FROM basecfg;";
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
}

$conn -> close();
?>