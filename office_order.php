<?php
include 'auth.php';

if(($_SESSION['role'] ?? '') !== 'manager'){
    http_response_code(403);
    echo json_encode(['status' => 'forbidden']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if(isset($data['order']) && is_array($data['order'])){
    $stmt = $pdo->prepare('UPDATE offices SET sort_order=? WHERE id=?');
    foreach($data['order'] as $item){
        if(!isset($item['id'])){
            continue;
        }
        $position = isset($item['position']) ? (int)$item['position'] : 0;
        $id = (int)$item['id'];
        $stmt->execute([$position, $id]);
    }
}

echo json_encode(['status' => 'ok']);
