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
    'zh' => ['项目编号', '项目名称', '项目描述', '开始日期', '结束日期', '状态', '背景色'],
    'en' => ['Project ID', 'Title', 'Description', 'Begin Date', 'End Date', 'Status', 'Background Color'],
];
$statusLabels = [
    'zh' => [
        'todo' => '未启动',
        'ongoing' => '进行中',
        'paused' => '暂停',
        'finished' => '已完成',
    ],
    'en' => [
        'todo' => 'Todo',
        'ongoing' => 'Ongoing',
        'paused' => 'Paused',
        'finished' => 'Finished',
    ],
];

$stmt = $pdo->query('SELECT id,title,description,begin_date,end_date,status,bg_color FROM projects ORDER BY sort_order');
$rows = [];
$labels = $headers[$lang];
$statusMap = $statusLabels[$lang];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = [
        $row['id'],
        $row['title'],
        $row['description'],
        $row['begin_date'],
        $row['end_date'],
        $statusMap[$row['status']] ?? $row['status'],
        $row['bg_color'],
    ];
}

$tmpFile = xlsx_write_workbook([
    ['name' => $lang === 'zh' ? '项目列表' : 'Projects', 'rows' => array_merge([$labels], $rows)],
]);

$filename = $lang === 'zh' ? 'projects.xlsx' : 'projects_en.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Content-Length: ' . filesize($tmpFile));
readfile($tmpFile);
@unlink($tmpFile);
exit;
