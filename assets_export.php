<?php
include 'auth.php';

if (($_SESSION['role'] ?? '') !== 'manager') {
    http_response_code(403);
    exit('Forbidden');
}

$lang = $_GET['lang'] ?? 'zh';
if (!in_array($lang, ['zh', 'en'], true)) {
    $lang = 'zh';
}

$headers = [
    'en' => [
        'Order #',
        'Asset Code',
        'Category',
        'Model / Configuration',
        'Owning Unit',
        'Remarks',
        'Current Location',
        'Person in Charge',
        'Status',
        'Updated'
    ],
    'zh' => [
        '入库单号',
        '资产编号',
        '资产类别',
        '型号配置',
        '所属单位',
        '备注',
        '当前地点',
        '责任人',
        '资产状态',
        '更新时间'
    ]
];

$statusLabels = [
    'en' => [
        'in_use' => 'In Use',
        'maintenance' => 'Under Maintenance',
        'pending' => 'Pending Allocation',
        'lost' => 'Lost',
        'retired' => 'Retired'
    ],
    'zh' => [
        'in_use' => '使用中',
        'maintenance' => '维修中',
        'pending' => '待分配',
        'lost' => '遗失',
        'retired' => '报废'
    ]
];

$query = 'SELECT a.asset_code, io.order_number, a.category, a.model, a.organization, a.remarks, '
    . 'o.name AS office_name, s.label AS seat_label, m.name AS owner_name, a.status, a.updated_at '
    . 'FROM assets a '
    . 'JOIN asset_inbound_orders io ON a.inbound_order_id = io.id '
    . 'LEFT JOIN offices o ON a.current_office_id = o.id '
    . 'LEFT JOIN office_seats s ON a.current_seat_id = s.id '
    . 'LEFT JOIN members m ON a.owner_member_id = m.id '
    . 'ORDER BY io.arrival_date DESC, a.id DESC';

$stmt = $pdo->query($query);
$rows = $stmt->fetchAll();

$filename = 'assets_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, $headers[$lang]);

foreach ($rows as $row) {
    $location = trim(((string)($row['office_name'] ?? '')) . (($row['seat_label'] ?? '') ? ' / ' . $row['seat_label'] : ''));
    $status = $row['status'] ?? '';
    $statusLabel = $statusLabels[$lang][$status] ?? $status;
    $data = [
        $row['order_number'] ?? '',
        $row['asset_code'] ?? '',
        $row['category'] ?? '',
        $row['model'] ?? '',
        $row['organization'] ?? '',
        $row['remarks'] ?? '',
        $location,
        $row['owner_name'] ?? '',
        $statusLabel,
        $row['updated_at'] ?? ''
    ];
    fputcsv($out, $data);
}

fclose($out);
exit;
