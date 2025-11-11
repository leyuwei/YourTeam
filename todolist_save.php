<?php
require 'auth.php';
$user_id = $_SESSION['role']==='manager' ? $_SESSION['manager_id'] : $_SESSION['member_id'];
$role = $_SESSION['role'];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? 'update';
header('Content-Type: application/json');
if($action === 'update'){
    $id = $data['id'] ?? null;
    $content = $data['content'] ?? '';
    $is_done = !empty($data['is_done']) ? 1 : 0;
    $category = $data['category'];
    $day = $data['day'] ?: null;
    $week_start = $data['week_start'];
    if($id){
        $stmt = $pdo->prepare('UPDATE todolist_items SET content=?, is_done=?, category=?, day=? WHERE id=? AND user_id=? AND user_role=?');
        $stmt->execute([$content,$is_done,$category,$day,$id,$user_id,$role]);
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
} elseif($action === 'copy_item_next'){
    $id = $data['id'];
    $week_start = $data['week_start'];
    $next_week_start = date('Y-m-d', strtotime($week_start.' +7 days'));
    $stmt = $pdo->prepare('INSERT INTO todolist_items (user_id,user_role,week_start,category,day,content,is_done,sort_order) SELECT user_id,user_role,?,category,day,content,0,sort_order FROM todolist_items WHERE id=? AND user_id=? AND user_role=?');
    $stmt->execute([$next_week_start,$id,$user_id,$role]);
    echo json_encode(['status'=>'ok']);
} elseif($action === 'tomorrow'){
    $id = $data['id'];
    $day = $data['day'];
    $week_start = $data['week_start'];
    $map = ['mon'=>'tue','tue'=>'wed','wed'=>'thu','thu'=>'fri','fri'=>'sat','sat'=>'sun'];
    if($day === 'sun'){
        $new_week_start = date('Y-m-d', strtotime($week_start.' +7 days'));
        $new_day = 'mon';
    } else {
        $new_week_start = $week_start;
        $new_day = $map[$day] ?? $day;
    }
    $stmt = $pdo->prepare('UPDATE todolist_items SET week_start=?, day=? WHERE id=? AND user_id=? AND user_role=?');
    $stmt->execute([$new_week_start,$new_day,$id,$user_id,$role]);
    echo json_encode(['status'=>'ok','new_day'=>$new_day,'new_week_start'=>$new_week_start]);
} elseif($action === 'common_create'){
    $content = trim($data['content'] ?? '');
    if($content === ''){
        http_response_code(400);
        echo json_encode(['error'=>'empty']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order),-1)+1 AS next_order FROM todolist_common_items WHERE user_id=? AND user_role=?');
    $stmt->execute([$user_id,$role]);
    $next_order = (int)$stmt->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO todolist_common_items (user_id,user_role,content,sort_order) VALUES (?,?,?,?)');
    $stmt->execute([$user_id,$role,$content,$next_order]);
    echo json_encode(['id'=>$pdo->lastInsertId()]);
} elseif($action === 'common_update'){
    $id = $data['id'] ?? null;
    $content = trim($data['content'] ?? '');
    if(!$id){
        http_response_code(400);
        echo json_encode(['error'=>'missing_id']);
        exit;
    }
    if($content === ''){
        http_response_code(400);
        echo json_encode(['error'=>'empty']);
        exit;
    }
    $stmt = $pdo->prepare('UPDATE todolist_common_items SET content=? WHERE id=? AND user_id=? AND user_role=?');
    $stmt->execute([$content,$id,$user_id,$role]);
    echo json_encode(['status'=>'ok']);
} elseif($action === 'common_delete'){
    $id = $data['id'] ?? null;
    if(!$id){
        http_response_code(400);
        echo json_encode(['error'=>'missing_id']);
        exit;
    }
    $stmt = $pdo->prepare('DELETE FROM todolist_common_items WHERE id=? AND user_id=? AND user_role=?');
    $stmt->execute([$id,$user_id,$role]);
    echo json_encode(['status'=>'ok']);
} elseif($action === 'common_order'){
    foreach(($data['order'] ?? []) as $o){
        if(!isset($o['id'])) continue;
        $position = isset($o['position']) ? (int)$o['position'] : 0;
        $stmt = $pdo->prepare('UPDATE todolist_common_items SET sort_order=? WHERE id=? AND user_id=? AND user_role=?');
        $stmt->execute([$position,$o['id'],$user_id,$role]);
    }
    echo json_encode(['status'=>'ok']);
}
?>
