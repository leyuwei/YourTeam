<?php
include 'auth.php';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="members.csv"');
$output = fopen('php://output', 'w');
// Output UTF-8 BOM to ensure correct display in Excel
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['CampusID','Name','Email','IdentityNumber','YearOfJoin','CurrentDegree','DegreePursuing','Phone','WeChat','Department','Workplace','Homeplace','Status']);
$stmt = $pdo->query('SELECT campus_id,name,email,identity_number,year_of_join,current_degree,degree_pursuing,phone,wechat,department,workplace,homeplace,status FROM members');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    fputcsv($output, $row);
}
fclose($output);
exit();
?>
