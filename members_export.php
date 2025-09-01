<?php
include 'auth.php';
$lang = $_GET['lang'] ?? 'zh';
$headers = [
    'en' => ['Campus ID','Name','Email','Identity Number','Year of Join','Current Degree','Degree Pursuing','Phone','WeChat','Department','Workplace','Homeplace','Status'],
    'zh' => ['一卡通号','姓名','正式邮箱','身份证号','入学年份','已获学位','当前学历','手机号','微信号','所处学院/单位','工作地点','家庭住址','状态']
];
$selectedHeaders = $headers[$lang] ?? $headers['zh'];

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="members.csv"');
$output = fopen('php://output', 'w');
// Output UTF-8 BOM to ensure correct display in Excel
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, $selectedHeaders);
$stmt = $pdo->query('SELECT campus_id,name,email,identity_number,year_of_join,current_degree,degree_pursuing,phone,wechat,department,workplace,homeplace,status FROM members');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $row = array_map(fn($v)=>'="'.$v.'"', $row);
    fputcsv($output, $row);
}
fclose($output);
exit();
?>
