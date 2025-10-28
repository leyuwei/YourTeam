<?php
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
$wantsJson = str_contains($acceptHeader, 'application/json') || $requestedWith === 'xmlhttprequest';
include 'auth.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $project_id = $_POST['project_id'] ?? null;
    $member_id = $_POST['member_id'] ?? null;
    $join_time = $_POST['join_time'] ?? null;
    if(!$project_id || !$member_id || !$join_time){
        if($wantsJson){
            header('Content-Type: application/json');
            echo json_encode(['status'=>'error','message'=>'project_members.invalid_request']);
            exit();
        }
        header('Location: projects.php');
        exit();
    }
    $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order),-1)+1 FROM project_member_log WHERE project_id=? AND exit_time IS NULL');
    $orderStmt->execute([$project_id]);
    $nextOrder = $orderStmt->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO project_member_log(project_id,member_id,join_time,sort_order) VALUES (?,?,?,?)');
    $stmt->execute([$project_id,$member_id,$join_time,$nextOrder]);
    $insertId = $pdo->lastInsertId();
    if($wantsJson){
        $memberStmt = $pdo->prepare('SELECT campus_id, name FROM members WHERE id=?');
        $memberStmt->execute([$member_id]);
        $member = $memberStmt->fetch();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'member' => [
                'log_id' => $insertId,
                'member_id' => $member_id,
                'campus_id' => $member['campus_id'] ?? '',
                'name' => $member['name'] ?? '',
                'join_time' => $join_time
            ],
            'history_entry' => [
                'log_id' => $insertId,
                'member_id' => $member_id,
                'campus_id' => $member['campus_id'] ?? '',
                'name' => $member['name'] ?? '',
                'join_time' => $join_time,
                'exit_time' => null
            ]
        ]);
        exit();
    }
    header('Location: project_members.php?id='.$project_id);
    exit();
}
if($wantsJson){
    header('Content-Type: application/json');
    echo json_encode(['status'=>'error','message'=>'project_members.invalid_request']);
    exit();
}
header('Location: projects.php');
?>
