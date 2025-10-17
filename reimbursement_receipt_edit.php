<?php
include 'auth.php';
include 'reimbursement_log.php';

$id = (int)($_GET['id'] ?? 0);
$is_manager = ($_SESSION['role'] === 'manager');
$member_id = $_SESSION['member_id'] ?? 0;

$stmt = $pdo->prepare("SELECT r.*, b.price_limit, b.status AS batch_status, b.title AS batch_title, b.allowed_types FROM reimbursement_receipts r JOIN reimbursement_batches b ON r.batch_id=b.id WHERE r.id=?");
$stmt->execute([$id]);
$rec = $stmt->fetch();

if(!$rec || (!$is_manager && $rec['member_id'] != $member_id)){
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'No permission'], JSON_UNESCAPED_UNICODE);
    exit;
}

$can_edit = ($rec['status']=='refused') || ($rec['status']=='submitted' && $rec['batch_status']=='open');
if(!$can_edit){
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Cannot edit this receipt'], JSON_UNESCAPED_UNICODE);
    exit;
}

$open_batches = $pdo->query("SELECT id,title,price_limit,allowed_types FROM reimbursement_batches WHERE status='open' ORDER BY deadline ASC")->fetchAll();
$allowed_map = [];
foreach($open_batches as $batch){
    $allowed_map[$batch['id']] = $batch['allowed_types'] ? explode(',', $batch['allowed_types']) : ['office','electronic','membership','book','trip'];
}
$currentAllowed = $rec['allowed_types'] ? explode(',', $rec['allowed_types']) : ['office','electronic','membership','book','trip'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'receipt' => [
            'id' => (int)$rec['id'],
            'batch_id' => (int)$rec['batch_id'],
            'category' => $rec['category'],
            'description' => $rec['description'],
            'price' => $rec['price'],
            'status' => $rec['status'],
            'requires_file' => ($rec['status']=='refused' && !$is_manager),
            'original_filename' => $rec['original_filename'],
            'stored_filename' => $rec['stored_filename'],
        ],
        'open_batches' => array_map(function($batch) use ($allowed_map){
            return [
                'id' => (int)$batch['id'],
                'title' => $batch['title'],
                'price_limit' => $batch['price_limit'],
                'allowed_types' => $allowed_map[$batch['id']] ?? ['office','electronic','membership','book','trip'],
            ];
        }, $open_batches),
        'current_allowed' => $currentAllowed,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$errors = [
    'batch' => 'Invalid batch.',
    'desc' => 'Description is required.',
    'type' => 'Type not allowed.',
    'exceed' => 'Price exceeds limit.',
    'file' => 'File is required.',
    'manager' => 'Managers cannot upload files.',
    'prohibited' => 'Receipt contains prohibited content.'
];

$target_batch_id = (int)($_POST['batch_id'] ?? $rec['batch_id']);
$batchStmt = $pdo->prepare("SELECT id,title, price_limit, allowed_types FROM reimbursement_batches WHERE id=?");
$batchStmt->execute([$target_batch_id]);
$targetBatch = $batchStmt->fetch();
if(!$targetBatch || ($targetBatch['status'] ?? '') === 'completed'){
    $error='batch';
} else {
    $allowedCats = $targetBatch['allowed_types'] ? explode(',', $targetBatch['allowed_types']) : ['office','electronic','membership','book','trip'];
    $category = $_POST['category'] ?? $rec['category'];
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] !== '' ? (float)$_POST['price'] : 0;
    if($description===''){
        $error='desc';
    } elseif(!in_array($category,$allowedCats)){
        $error='type';
    } else {
        $batchLimit = $targetBatch['price_limit'];
        $totalStmt = $pdo->prepare("SELECT COALESCE(SUM(price),0) FROM reimbursement_receipts WHERE batch_id=? AND member_id=? AND id<>? AND status<>'refused'");
        $totalStmt->execute([$target_batch_id,$rec['member_id'],$id]);
        $currentTotal = (float)$totalStmt->fetchColumn();
        if(!$is_manager && $batchLimit !== null && $currentTotal + $price > $batchLimit){
            $error='exceed';
        } else {
            $memberInfo = $pdo->prepare("SELECT name,campus_id FROM members WHERE id=?");
            $memberInfo->execute([$rec['member_id']]);
            $mi = $memberInfo->fetch();
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM reimbursement_receipts WHERE batch_id=? AND member_id=? AND id<>? AND status<>'refused'");
            $countStmt->execute([$target_batch_id,$rec['member_id'],$id]);
            $index = $countStmt->fetchColumn()+1;
            $base = $mi['name'].'-'.$mi['campus_id'].'-'.$targetBatch['title'].'-'.$index;
            $orig = $rec['original_filename'];
            $stored = $rec['stored_filename'];
            if($rec['status']=='refused'){
                if($is_manager){
                    $error='manager';
                } elseif(!isset($_FILES['receipt']) || $_FILES['receipt']['error']!==UPLOAD_ERR_OK){
                    $error='file';
                } else {
                    $orig = $_FILES['receipt']['name'];
                    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                    $tmpPath = $_FILES['receipt']['tmp_name'];
                    $orig_base = pathinfo($orig, PATHINFO_FILENAME);
                    $suffix = mt_rand(1000,9999) . '-' . time();
                    $orig = $orig_base . '-' . $suffix . '.' . $ext;
                    if($ext==='pdf'){
                        $keywords=$pdo->query("SELECT keyword FROM reimbursement_prohibited_keywords")->fetchAll(PDO::FETCH_COLUMN);
                        $content=@shell_exec('pdftotext '.escapeshellarg($tmpPath).' -');
                        if(!$content){ $content=@file_get_contents($tmpPath); }
                        foreach($keywords as $kw){
                            if($kw!=='' && stripos($content,$kw)!==false){
                                $error='prohibited';
                                break;
                            }
                        }
                    }
                    if(!isset($error)){
                        $stored = $base . '-' . $suffix . '.' . $ext;
                        $dir = __DIR__.'/reimburse_uploads/'.$target_batch_id;
                        if(!is_dir($dir)) mkdir($dir,0777,true);
                        move_uploaded_file($tmpPath, $dir.'/'.$stored);
                        @unlink(__DIR__.'/reimburse_uploads/'.$rec['batch_id'].'/'.$rec['stored_filename']);
                    }
                }
            } else {
                $ext = pathinfo($stored, PATHINFO_EXTENSION);
                $storedBase = pathinfo($stored, PATHINFO_FILENAME);
                if($target_batch_id != $rec['batch_id'] || strpos($storedBase, $base . '-') !== 0){
                    $newname = $base . '-' . mt_rand(1000,9999) . '-' . time() . '.' . $ext;
                    $src = __DIR__.'/reimburse_uploads/'.$rec['batch_id'].'/'.$stored;
                    $dir = __DIR__.'/reimburse_uploads/'.$target_batch_id;
                    if(!is_dir($dir)) mkdir($dir,0777,true);
                    rename($src, $dir.'/'.$newname);
                    $stored = $newname;
                }
            }
            if(!isset($error)){
                $stmt = $pdo->prepare("UPDATE reimbursement_receipts SET batch_id=?, original_filename=?, stored_filename=?, category=?, description=?, price=?, status='submitted' WHERE id=?");
                $stmt->execute([$target_batch_id,$orig,$stored,$category,$description,$price,$id]);
                $changes=[];
                if($rec['batch_id']!=$target_batch_id) $changes[]='batch';
                if($rec['category']!=$category) $changes[]='category';
                if($rec['description']!=$description) $changes[]='description';
                if($rec['price']!=$price) $changes[]='price';
                $msg='Receipt '.$id.' updated'.($changes?': '.implode(', ',$changes):'');
                add_batch_log($pdo,$target_batch_id,$_SESSION['username'],$msg);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'redirect' => 'reimbursement_batch.php?id='.$target_batch_id], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    }
}

if(isset($error)){
    header('Content-Type: application/json; charset=utf-8');
    $message = $errors[$error] ?? 'Failed to update receipt.';
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => false, 'message' => 'Failed to update receipt.'], JSON_UNESCAPED_UNICODE);
exit;
