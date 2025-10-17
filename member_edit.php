<?php
require 'auth_manager.php';
require_once 'member_attribute_helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function build_member_payload(PDO $pdo, ?int $id): array
{
    $member = [
        'id' => null,
        'campus_id' => '',
        'name' => '',
        'email' => '',
        'identity_number' => '',
        'year_of_join' => '',
        'current_degree' => '',
        'degree_pursuing' => '',
        'phone' => '',
        'wechat' => '',
        'department' => '',
        'workplace' => '',
        'homeplace' => '',
        'status' => 'in_work'
    ];
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM members WHERE id=?');
        $stmt->execute([$id]);
        $found = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($found) {
            $member = array_merge($member, $found);
        }
    }
    $attributes = fetch_member_attributes($pdo);
    $values = [];
    if ($id) {
        $map = fetch_member_attribute_map($pdo, [$id]);
        $values = $map[$id] ?? [];
    } else {
        foreach ($attributes as $attr) {
            $values[$attr['id']] = $attr['default_value'];
        }
    }
    return ['member' => $member, 'attributes' => $attributes, 'attribute_values' => $values];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true] + build_member_payload($pdo, $id), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid method'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: members.php');
    exit;
}

$raw = file_get_contents('php://input');
$data = [];
if ($raw !== false && trim($raw) !== '') {
    $data = json_decode($raw, true) ?? [];
}
if (empty($data)) {
    $data = $_POST;
}

$campus_id = trim($data['campus_id'] ?? '');
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$identity_number = trim($data['identity_number'] ?? '');
$year_of_join = trim($data['year_of_join'] ?? '');
$current_degree = trim($data['current_degree'] ?? '');
$degree_pursuing = trim($data['degree_pursuing'] ?? '');
$phone = trim($data['phone'] ?? '');
$wechat = trim($data['wechat'] ?? '');
$department = trim($data['department'] ?? '');
$workplace = trim($data['workplace'] ?? '');
$homeplace = trim($data['homeplace'] ?? '');
$status = $data['status'] ?? 'in_work';
$attributesPayload = $data['attributes'] ?? [];

if ($name === '' || $campus_id === '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Name and campus id are required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($id) {
    $stmt = $pdo->prepare('UPDATE members SET campus_id=?, name=?, email=?, identity_number=?, year_of_join=?, current_degree=?, degree_pursuing=?, phone=?, wechat=?, department=?, workplace=?, homeplace=?, status=? WHERE id=?');
    $stmt->execute([$campus_id,$name,$email,$identity_number,$year_of_join,$current_degree,$degree_pursuing,$phone,$wechat,$department,$workplace,$homeplace,$status,$id]);
    $memberId = $id;
} else {
    $orderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order),-1)+1 FROM members');
    $nextOrder = $orderStmt->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO members(campus_id,name,email,identity_number,year_of_join,current_degree,degree_pursuing,phone,wechat,department,workplace,homeplace,status,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$campus_id,$name,$email,$identity_number,$year_of_join,$current_degree,$degree_pursuing,$phone,$wechat,$department,$workplace,$homeplace,$status,$nextOrder]);
    $memberId = (int)$pdo->lastInsertId();
    assign_defaults_to_member($pdo, $memberId);
}

$attributes = fetch_member_attributes($pdo);
$values = [];
foreach ($attributes as $attr) {
    $attrId = (int)$attr['id'];
    $values[$attrId] = $attributesPayload[$attrId] ?? $attr['default_value'];
}
upsert_member_attribute_values($pdo, $memberId, $values);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'id' => $memberId], JSON_UNESCAPED_UNICODE);
exit;
