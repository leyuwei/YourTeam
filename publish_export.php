<?php
include 'auth.php';
require_once 'config.php';
require_once 'publish_helpers.php';

if (($_SESSION['role'] ?? '') !== 'manager') {
    http_response_code(403);
    exit('Forbidden');
}

$lang = $_GET['lang'] ?? 'zh';
if (!in_array($lang, ['zh', 'en'], true)) {
    $lang = 'zh';
}

$attributes = getPublishAttributes($pdo);

$columns = [];
$columns[] = $lang === 'en' ? 'Member' : '成员';
foreach ($attributes as $attr) {
    $name = $lang === 'en' ? trim((string)$attr['name_en']) : trim((string)$attr['name_zh']);
    if ($name === '') {
        $name = trim((string)($lang === 'en' ? $attr['name_zh'] : $attr['name_en']));
    }
    $columns[] = $name !== '' ? $name : 'Attribute #' . $attr['id'];
}
$columns[] = $lang === 'en' ? 'Updated' : '更新时间';

$stmt = $pdo->query('SELECT e.id, e.member_id, e.updated_at, m.name AS member_name FROM publish_entries e JOIN members m ON e.member_id = m.id ORDER BY e.updated_at DESC, e.id DESC');
$entries = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
$entryIds = array_column($entries, 'id');
$valuesMap = getPublishValues($pdo, $entryIds);

$filename = 'publish_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, $columns);

foreach ($entries as $entry) {
    $entryId = (int)($entry['id'] ?? 0);
    $row = [];
    $row[] = $entry['member_name'] ?? '';
    foreach ($attributes as $attr) {
        $attrId = (int)($attr['id'] ?? 0);
        $row[] = $valuesMap[$entryId][$attrId] ?? ($attr['attribute_type'] === 'file' ? '' : (string)($attr['default_value'] ?? ''));
    }
    $row[] = $entry['updated_at'] ?? '';
    fputcsv($out, $row);
}

fclose($out);
exit;
