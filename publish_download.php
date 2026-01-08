<?php
include 'auth.php';
require_once 'config.php';
require_once 'publish_helpers.php';

if (($_SESSION['role'] ?? '') !== 'manager') {
    http_response_code(403);
    exit('Forbidden');
}

$attributes = getPublishAttributes($pdo);
$fileAttributeIds = [];
foreach ($attributes as $attr) {
    if (($attr['attribute_type'] ?? '') === 'file') {
        $fileAttributeIds[] = (int)$attr['id'];
    }
}

if (empty($fileAttributeIds)) {
    http_response_code(404);
    exit('No file attributes configured.');
}

$stmt = $pdo->query('SELECT e.id, e.member_id, m.name AS member_name FROM publish_entries e JOIN members m ON e.member_id = m.id ORDER BY e.id');
$entries = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
$entryIds = array_column($entries, 'id');
$valuesMap = getPublishValues($pdo, $entryIds);

$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'publish_zip_');
if ($tmpFile === false || $zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('Unable to create ZIP.');
}

$added = false;
foreach ($entries as $entry) {
    $entryId = (int)($entry['id'] ?? 0);
    $memberName = trim((string)($entry['member_name'] ?? ''));
    $memberFolder = $memberName !== '' ? $memberName : ('member_' . ($entry['member_id'] ?? ''));
    foreach ($fileAttributeIds as $attrId) {
        $value = (string)($valuesMap[$entryId][$attrId] ?? '');
        if ($value === '') {
            continue;
        }
        $path = __DIR__ . '/' . ltrim($value, '/');
        if (!is_file($path)) {
            continue;
        }
        $basename = basename($path);
        $zipPath = $memberFolder . '/entry_' . $entryId . '/' . $basename;
        $zip->addFile($path, $zipPath);
        $added = true;
    }
}

$zip->close();

if (!$added) {
    @unlink($tmpFile);
    http_response_code(404);
    exit('No files available.');
}

$filename = 'publish_files_' . date('Ymd_His') . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmpFile));
readfile($tmpFile);
@unlink($tmpFile);
exit;
