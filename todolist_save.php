<?php
require 'auth.php';
$user_id = $_SESSION['role']==='manager' ? $_SESSION['manager_id'] : $_SESSION['member_id'];
$role = $_SESSION['role'];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? 'update';
if($action === 'update'){
    $id = $data['id'] ?? null;
    $content = $data['content'] ?? '';
    $is_done = !empty($data['is_done']) ? 1 : 0;
    $category = $data['category'];
    $day = $data['day'] ?: null;
    $week_start = $data['week_start'];
    if($id){
        $stmt = $pdo->prepare('UPDATE todolist_items SET content=?, is_done=? WHERE id=? AND user_id=? AND user_role=?');
        $stmt->execute([$content,$is_done,$id,$user_id,$role]);
        echo json_encode(['id'=>$id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO todolist_items (user_id,user_role,week_start,category,day,content,is_done,sort_order) VALUES (?,?,?,?,?,?,?,0)');
        $stmt->execute([$user_id,$role,$week_start,$category,$day,$content,$is_done]);
        echo json_encode(['id'=>$pdo->lastInsertId()]);
    }
} elseif($action === 'delete'){
    $id = $data['id'];
    $stmt = $pdo->prepare('DELETE FROM todolist_items WHERE id=? AND user_id=? AND user_role=?');
    $stmt->execute([$id,$user_id,$role]);
    echo json_encode(['status'=>'ok']);
} elseif($action === 'order'){
    foreach($data['order'] as $o){
        $stmt = $pdo->prepare('UPDATE todolist_items SET sort_order=? WHERE id=? AND user_id=? AND user_role=?');
        $stmt->execute([$o['position'],$o['id'],$user_id,$role]);
    }
    echo json_encode(['status'=>'ok']);
} elseif($action === 'copy_next'){
    $week_start = $data['week_start'];
    $next_week_start = date('Y-m-d', strtotime($week_start.' +7 days'));
    $stmt = $pdo->prepare('INSERT INTO todolist_items (user_id,user_role,week_start,category,day,content,is_done,sort_order) SELECT user_id,user_role,?,category,day,content,0,sort_order FROM todolist_items WHERE user_id=? AND user_role=? AND week_start=? AND is_done=0');
    $stmt->execute([$next_week_start,$user_id,$role,$week_start]);
    echo json_encode(['status'=>'ok']);
}
?>
