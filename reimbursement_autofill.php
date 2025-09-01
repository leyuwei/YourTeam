<?php
include 'auth.php';
header('Content-Type: application/json');

if(!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK){
    echo json_encode(['price'=>0, 'category'=>'']);
    exit;
}

$tmpPath = $_FILES['receipt']['tmp_name'];

$command = ['pdftotext', $tmpPath, '-'];
$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
];
$process = proc_open($command, $descriptors, $pipes, null, ['LANG' => 'zh_CN.UTF-8'], ['bypass_shell' => true]);

if (is_resource($process)) {
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $result = proc_close($process);
    if ($result !== 0) {
        error_log('pdftotext failed: ' . $error);
        echo json_encode(['price' => 0, 'category' => '']);
        exit;
    }
    $text = mb_convert_encoding($output, 'UTF-8', 'auto');
} else {
    echo json_encode(['price' => 0, 'category' => '']);
    exit;
}

$price = 0;
if (preg_match_all('/[\x{00A5}\x{FFE5}]\\s*(\\d+(?:\\.\\d+)?)/u', $text, $matches)) {
    $prices = array_map('floatval', $matches[1]);
    if ($prices) {
        $price = max($prices);
    }
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

