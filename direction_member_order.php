<?php
include 'auth.php';
$data = json_decode(file_get_contents('php://input'), true);
if(isset($data['direction_id']) && isset($data['order'])){
    $direction_id = $data['direction_id'];
    $stmt = $pdo->prepare('UPDATE direction_members SET sort_order=? WHERE direction_id=? AND member_id=?');
    foreach($data['order'] as $index => $member_id){
        $stmt->execute([$index, $direction_id, $member_id]);
    }
}
echo json_encode(['status'=>'ok']);
?>
