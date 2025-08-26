<?php
include 'auth.php';
header('Content-Type: application/json');

if(!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK){
    echo json_encode(['price'=>0, 'category'=>'']);
    exit;
}

$tmpPath = $_FILES['receipt']['tmp_name'];
$escapedPath = escapeshellarg($tmpPath);
$command = "LANG=zh_CN.UTF-8 pdftotext {$escapedPath} - 2>&1";
$output = shell_exec($command);
$text = mb_convert_encoding($output, 'UTF-8', 'auto');

$price = 0;
if(preg_match('/[¥￥]\\s*(\\d+(?:\\.\\d+)?)/u', $text, $m)){
    $price = $m[1];
}

$category = '';
$categories = [
    'electronic' => ['机械','电子','材料','配件','电气','设备','通信','设施','通讯','数据'],
    'book' => ['书','图书','教材','教学'],
    'office' => ['纸','笔','盘','办公'],
    'trip' => ['滴滴','t3','携程','高德','小猪','行程','美团','百度','曹操','青桔'],
    'membership' => ['会员','会费','注册']
];
foreach($categories as $cat => $keywords){
    foreach($keywords as $kw){
        if(strpos($text, $kw) !== false){
            $category = $cat;
            break 2;
        }
    }
}

echo json_encode(['price'=>$price, 'category'=>$category]);
?>

