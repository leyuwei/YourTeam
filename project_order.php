<?php
include 'auth.php';
$data = json_decode(file_get_contents('php://input'), true);
if(isset($data['order'])){
    $stmt = $pdo->prepare('UPDATE projects SET sort_order=? WHERE id=?');
    foreach($data['order'] as $item){
        $stmt->execute([$item['position'], $item['id']]);
    }
}
echo json_encode(['status'=>'ok']);
?>
