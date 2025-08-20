<?php

header("Content-Type:application/json; charset=utf-8");

include "connection.php";

if ($_POST["checkerpaper"] == "false" && $_FILES["file"]["size"] >= 10000000)
{
    die("{\"filename\": \"" . $_FILES["file"]["name"] . "\", \"filesize\": \"" . $_FILES["file"]["size"] . "\", \"result\": \"0\", \"content\": \"无效文件！ - 发票尺寸超限\"}");
}

if ($_POST["checkerpaper"] == "false" && $_FILES["file"]["error"] > 0)
{
    echo "{\"filename\": \"" . $_FILES["file"]["name"] . "\", \"filesize\": \"" . $_FILES["file"]["size"] . "\", \"result\": \"0\", \"content\": \"上传过程遇到错误:" . $_FILES["file"]["error"] . "\"}";
}
else
{
    $path = "/var/www/html/uploads/";
    if ($_POST["checkerpaper"] == "false" && file_exists($path . $_POST["username"] . "_" . $_POST["usernumber"] . "_" . $_FILES["file"]["name"]))
      {
        echo "{\"filename\": \"" . $_FILES["file"]["name"] . "\", \"filesize\": \"" . $_FILES["file"]["size"] . "\", \"result\": \"0\", \"content\": \"文件已存在，若您确定本发票未被上传过请更改文件名称后重试\"}";
      }
    else
      {
            $batch = isset($_POST["batch"]) ? $_POST["batch"] : 1;
            if ($_POST["checkerpaper"] == "false") {
                $new_filename = $_POST["username"] . "_" . $_POST["usernumber"] . "_" . $_FILES["file"]["name"];
                move_uploaded_file($_FILES["file"]["tmp_name"], $path . $new_filename);

                // Extract text from the PDF using pdftotext (Poppler must be installed on the server)
                $pdfFile = $path . $new_filename;

                $query_statement = "INSERT INTO records (id, username, userid, filename, filetype, fileprice, isfinished, uploadtime, checkerquote, batch) VALUES (NULL, \"" . $_POST["username"] . "\", \"" . $_POST["usernumber"] . "\", \"" . $new_filename . "\", \"" . $_POST["receipttype"] . "\", \"" . $_POST["receiptprice"] . "\", \"0\", CURRENT_TIMESTAMP(),  \"" . $_POST["checkerquote"] . "\", \"" . $batch . "\");";
                $db_query = $conn -> query($query_statement);
                if (!$db_query) {
                    echo "{\"filename\": \"" . $new_filename . "\", \"filesize\": \"" . $_FILES["file"]["size"] . "\", \"result\": \"0\", \"content\": \"上传发生错误:" . $conn->error . "\"}";
                } else {
                    $command =  "LANG=zh_CN.UTF-8 pdftotext " . $pdfFile . " - 2>&1";
                    $output = shell_exec($command);
                    // echo "{\"filename\": \"" . $new_filename . "\", \"filesize\": \"" . $_FILES["file"]["size"] . "\", \"result\": \"1\", \"content\": \"上传成功\"}";
                    echo json_encode([
                        "filename" => $new_filename,
                        "filesize" => $_FILES["file"]["size"],
                        "result"   => "1",
                        "content"  => "上传成功",
                        "parse"    => $output
                    ],
                    JSON_UNESCAPED_UNICODE);
                }
            } else {
                $filename = $_POST["username"] . "_" . $_POST["usernumber"] . "_纸质发票_" . time() . ".txt";
                file_put_contents($path . $filename, "This is a paper receipt. Please replace me with the original receipt file.");
                $query_statement = "INSERT INTO records (id, username, userid, filename, filetype, fileprice, isfinished, uploadtime, checkerquote, batch) VALUES (NULL, \"" . $_POST["username"] . "\", \"" . $_POST["usernumber"] . "\", \"" . $filename . "\", \"" . $_POST["receipttype"] . "\", \"" . $_POST["receiptprice"] . "\", \"0\", CURRENT_TIMESTAMP(),  \"" . $_POST["checkerquote"] . "\", \"" . $batch . "\");";
                $db_query = $conn -> query($query_statement);
                if (!$db_query) {
                    echo "{\"filename\": \"" . $filename . "\", \"filesize\": \"" . 1 . "\", \"result\": \"0\", \"content\": \"上传发生错误:" . $conn->error . "\"}";
                } else {
                    echo "{\"filename\": \"" . $filename . "\", \"filesize\": \"" . 1 . "\", \"result\": \"1\", \"content\": \"上传成功\"}";
                }
            }
      }
}


$conn -> close();
?>