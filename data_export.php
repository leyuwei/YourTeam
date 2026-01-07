<?php
require_once 'auth_manager.php';
require_once 'data_transfer.php';

set_time_limit(0);

$uploadFolders = [
    'asset_uploads',
    'regulation_uploads',
    'reimburse_uploads',
    'office_layouts',
    'collect_uploads',
];

$zipPath = tempnam(sys_get_temp_dir(), 'team_export_');
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('Failed to create export.');
}

$sqlDump = create_database_dump($pdo);
$zip->addFromString('database.sql', $sqlDump);

foreach ($uploadFolders as $folder) {
    $source = __DIR__ . '/' . $folder;
    if (is_dir($source)) {
        add_directory_to_zip($zip, $source, $folder);
    }
}

$zip->close();

if (ob_get_length()) {
    ob_end_clean();
}

$filename = 'team_export_' . date('Ymd_His') . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
unlink($zipPath);
exit();
