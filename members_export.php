<?php
include 'auth.php';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="members.csv"');
$output = fopen('php://output', 'w');
fputcsv($output, ['CampusID','Name','Email','IdentityNumber','YearOfJoin','CurrentDegree','DegreePursuing','Phone','WeChat','Department','Workplace','Homeplace']);
$stmt = $pdo->query('SELECT campus_id,name,email,identity_number,year_of_join,current_degree,degree_pursuing,phone,wechat,department,workplace,homeplace FROM members');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    fputcsv($output, $row);
}
fclose($output);
exit();
?>
