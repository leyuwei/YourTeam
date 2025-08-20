<?php
include 'auth.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, m.name AS in_charge_name FROM reimbursement_batches b LEFT JOIN members m ON b.in_charge_member_id=m.id WHERE b.id=?");
$stmt->execute([$id]);
$batch = $stmt->fetch();
if(!$batch){
    exit('Batch not found');
}
$member_id = $_SESSION['member_id'] ?? null;
$is_manager = ($_SESSION['role'] === 'manager');
if(!($is_manager || $batch['in_charge_member_id']==$member_id)){
    exit('No permission');
}
$receipts = $pdo->prepare("SELECT r.*, mb.name AS member_name FROM reimbursement_receipts r JOIN members mb ON r.member_id=mb.id WHERE r.batch_id=?");
$receipts->execute([$id]);
$items = $receipts->fetchAll();
$zip = new ZipArchive();
$tmp = tempnam(sys_get_temp_dir(),'zip');
$zip->open($tmp, ZipArchive::CREATE);
$fp = fopen('php://temp', 'r+');
fputs($fp, "\xEF\xBB\xBF");
fputcsv($fp, ['id','member','original_filename','category','description','price','status','uploaded_at']);
foreach($items as $r){
    $path = __DIR__."/reimburse_uploads/".$id."/".$r['stored_filename'];
    if(is_file($path)){
        $zip->addFile($path, $r['original_filename']);
    }
    fputcsv($fp, [$r['id'],$r['member_name'],$r['original_filename'],$r['category'],$r['description'],$r['price'],$r['status'],$r['uploaded_at']]);
}
rewind($fp);
$csv = stream_get_contents($fp);
fclose($fp);
$zip->addFromString('summary.csv', $csv);
$zip->close();
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="reimbursement_batch_'.$id.'.zip"');
header('Content-Length: ' . filesize($tmp));
readfile($tmp);
unlink($tmp);
exit;
