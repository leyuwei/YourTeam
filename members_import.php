<?php
require 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
        $handle = fopen($_FILES['file']['tmp_name'], 'r');
        // skip header row
        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 12) {
                continue; // skip malformed rows
            }
            list($campus_id, $name, $email, $identity_number, $year_of_join, $current_degree, $degree_pursuing, $phone, $wechat, $department, $workplace, $homeplace) = $row;
            $stmt = $pdo->prepare('INSERT INTO members(campus_id,name,email,identity_number,year_of_join,current_degree,degree_pursuing,phone,wechat,department,workplace,homeplace) VALUES (?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name), email=VALUES(email), identity_number=VALUES(identity_number), year_of_join=VALUES(year_of_join), current_degree=VALUES(current_degree), degree_pursuing=VALUES(degree_pursuing), phone=VALUES(phone), wechat=VALUES(wechat), department=VALUES(department), workplace=VALUES(workplace), homeplace=VALUES(homeplace)');
            $stmt->execute([$campus_id, $name, $email, $identity_number, $year_of_join, $current_degree, $degree_pursuing, $phone, $wechat, $department, $workplace, $homeplace]);
        }
        fclose($handle);
        header('Location: members.php');
        exit();
    }
}

include 'header.php';
?>
<h2>Import Members from Excel (CSV)</h2>
<form method="post" enctype="multipart/form-data">
  <div class="mb-3">
    <input type="file" name="file" accept=".csv" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">Import</button>
  <a href="members.php" class="btn btn-secondary">Cancel</a>
</form>
<?php include 'footer.php'; ?>
