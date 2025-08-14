<?php
require 'auth.php';
$user_id = $_SESSION['role']==='manager' ? $_SESSION['manager_id'] : $_SESSION['member_id'];
$role = $_SESSION['role'];
$week_param = $_GET['week'] ?? date('o-\WW');
if(preg_match('/^(\d{4})-W(\d{2})$/',$week_param,$m)){
    $dt = new DateTime();
    $dt->setISODate($m[1], $m[2]);
    $week_start = $dt->format('Y-m-d');
} else {
    $dt = new DateTime();
    $dt->setISODate(date('o'), date('W'));
    $week_start = $dt->format('Y-m-d');
    $week_param = $dt->format('o-\WW');
}
$stmt = $pdo->prepare('SELECT category, day, content, is_done FROM todolist_items WHERE user_id=? AND user_role=? AND week_start=? ORDER BY category, day, sort_order');
$stmt->execute([$user_id,$role,$week_start]);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="todolist.csv"');
echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
echo "category,day,content,is_done\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $content = str_replace('"','""',$row['content']);
    echo $row['category'].",".$row['day'].",\"$content\",".$row['is_done']."\n";
}
?>
