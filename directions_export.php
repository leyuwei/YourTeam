<?php
require 'auth.php';
require_once 'xlsx_helper.php';

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    echo 'ZipArchive support is required.';
    exit;
}

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'zh');
$lang = in_array($lang, ['zh', 'en'], true) ? $lang : 'zh';

$headers = [
    'zh' => ['方向编号', '研究方向', '方向描述', '背景色'],
    'en' => ['Direction ID', 'Title', 'Description', 'Background Color'],
];

$stmt = $pdo->query('SELECT id,title,description,bg_color FROM research_directions ORDER BY sort_order');
$rows = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = [
        $row['id'],
        $row['title'],
        $row['description'],
        $row['bg_color'],
    ];
}

$tmpFile = xlsx_write_workbook([
    ['name' => $lang === 'zh' ? '研究方向' : 'Directions', 'rows' => array_merge([$headers[$lang]], $rows)],
]);

$filename = $lang === 'zh' ? 'directions.xlsx' : 'directions_en.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Content-Length: ' . filesize($tmpFile));
readfile($tmpFile);
@unlink($tmpFile);
exit;
