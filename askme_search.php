<?php
include 'auth.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
$viewportWidth = (int)($_GET['width'] ?? 0);
if ($q === '') {
    echo json_encode(['results' => []]);
    exit;
}

function calculate_radius(int $viewportWidth): int {
    $minRadius = 40;
    $maxRadius = 200;
    if ($viewportWidth <= 0) {
        return $minRadius;
    }
    $estimatedChars = (int)round($viewportWidth / 6);
    $radius = (int)floor($estimatedChars / 2);
    return max($minRadius, min($maxRadius, $radius));
}

$snippetRadius = calculate_radius($viewportWidth);

function make_snippet(string $text, string $keyword, int $radius): string {
    $plain = strip_tags($text);
    $pos = mb_stripos($plain, $keyword);
    if ($pos === false) {
        $pos = 0;
    }
    $start = max($pos - $radius, 0);
    $length = mb_strlen($keyword) + $radius * 2;
    $snippet = mb_substr($plain, $start, $length);
    if ($start > 0) {
        $snippet = '…' . $snippet;
    }
    if ($start + $length < mb_strlen($plain)) {
        $snippet .= '…';
    }
    $escapedSnippet = htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8');
    $safeKeyword = preg_quote(htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'), '/');
    $highlighted = preg_replace('/(' . $safeKeyword . ')/iu', '<mark>$1</mark>', $escapedSnippet);
    return $highlighted ?? $escapedSnippet;
}

$like = '%' . $q . '%';
$results = [];

// Regulations (regulation_files)
$stmt = $pdo->prepare("SELECT rf.id, rf.original_filename, r.category, r.description FROM regulation_files rf JOIN regulations r ON rf.regulation_id = r.id WHERE rf.original_filename LIKE ? OR r.category LIKE ? OR r.description LIKE ? ORDER BY r.updated_at DESC LIMIT 15");
$stmt->execute([$like, $like, $like]);
foreach ($stmt->fetchAll() as $row) {
    $text = implode(' ', array_filter([$row['original_filename'], $row['category'], $row['description']], fn($v) => $v !== null && $v !== ''));
    $results[] = [
        'source' => 'regulation_files',
        'source_label' => '政策与流程 / Regulations',
        'title' => $row['original_filename'],
        'snippet' => make_snippet($text, $q, $snippetRadius),
        'download_url' => 'regulation_file.php?id=' . $row['id'],
    ];
}

// Offices and occupants (support searching by office or member name)
$stmt = $pdo->prepare(
    "SELECT DISTINCT o.id, o.name, o.location_description, o.region
     FROM offices o
     LEFT JOIN office_seats s ON s.office_id = o.id
     LEFT JOIN members m ON m.id = s.member_id
     WHERE o.name LIKE ? OR o.location_description LIKE ? OR o.region LIKE ? OR m.name LIKE ?
     ORDER BY o.sort_order"
);
$stmt->execute([$like, $like, $like, $like]);
$officeRows = $stmt->fetchAll();

$memberStmt = $pdo->prepare(
    "SELECT m.name, s.label AS seat_label
     FROM office_seats s
     LEFT JOIN members m ON m.id = s.member_id
     WHERE s.office_id = ? AND s.member_id IS NOT NULL
     ORDER BY m.name"
);

foreach ($officeRows as $row) {
    $memberStmt->execute([(int)$row['id']]);
    $members = array_map(function ($memberRow) {
        return [
            'name' => $memberRow['name'],
            'seat' => $memberRow['seat_label']
        ];
    }, $memberStmt->fetchAll());

    $peopleNames = implode(' ', array_column($members, 'name'));
    $text = trim(implode(' ', array_filter([
        $row['name'] ?? '',
        $row['location_description'] ?? '',
        $row['region'] ?? '',
        $peopleNames
    ], fn($v) => $v !== null && $v !== '')));

    $results[] = [
        'source' => 'offices',
        'source_label' => '办公地点 / Offices',
        'title' => $row['name'],
        'snippet' => make_snippet($text !== '' ? $text : $row['name'], $q, $snippetRadius),
        'members' => $members,
    ];
}

// Assets
$stmt = $pdo->prepare("SELECT asset_code, category, model, organization, remarks FROM assets WHERE asset_code LIKE ? OR category LIKE ? OR model LIKE ? OR organization LIKE ? OR remarks LIKE ? ORDER BY updated_at DESC LIMIT 15");
$stmt->execute([$like, $like, $like, $like, $like]);
foreach ($stmt->fetchAll() as $row) {
    $text = implode(' ', array_filter([$row['asset_code'], $row['category'], $row['model'], $row['organization'], $row['remarks']], fn($v) => $v !== null && $v !== ''));
    $results[] = [
        'source' => 'assets',
        'source_label' => '固定资产 / Assets',
        'title' => $row['asset_code'],
        'snippet' => make_snippet($text, $q, $snippetRadius)
    ];
}

// AskMe Knowledge Base
$stmt = $pdo->prepare("SELECT content, keywords FROM askme_entries WHERE content LIKE ? OR keywords LIKE ? ORDER BY updated_at DESC");
$stmt->execute([$like, $like]);
foreach ($stmt->fetchAll() as $row) {
    $text = $row['content'];
    $results[] = [
        'source' => 'askme_entries',
        'source_label' => 'AskMe 知识库',
        'title' => $row['keywords'],
        'snippet' => make_snippet($text, $q, $snippetRadius),
        'content' => $text,
    ];
}

echo json_encode(['results' => $results]);
