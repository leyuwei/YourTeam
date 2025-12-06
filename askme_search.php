<?php
include 'auth.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode(['results' => []]);
    exit;
}

function make_snippet(string $text, string $keyword, int $radius = 40): string {
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

// Regulations
$stmt = $pdo->prepare("SELECT description, category FROM regulations WHERE description LIKE ? OR category LIKE ? ORDER BY updated_at DESC LIMIT 10");
$stmt->execute([$like, $like]);
foreach ($stmt->fetchAll() as $row) {
    $base = $row['description'] ?: $row['category'];
    $results[] = [
        'source' => 'regulation',
        'source_label' => '政策与流程 / Regulations',
        'title' => $row['category'],
        'snippet' => make_snippet($base, $q)
    ];
}

// Offices
$stmt = $pdo->prepare("SELECT name, location_description, region FROM offices WHERE name LIKE ? OR location_description LIKE ? OR region LIKE ? ORDER BY sort_order");
$stmt->execute([$like, $like, $like]);
foreach ($stmt->fetchAll() as $row) {
    $text = ($row['location_description'] ?: '') . ' ' . ($row['region'] ?: '');
    $results[] = [
        'source' => 'office',
        'source_label' => '办公地点 / Offices',
        'title' => $row['name'],
        'snippet' => make_snippet($text !== ' ' ? $text : $row['name'], $q)
    ];
}

// Assets
$stmt = $pdo->prepare("SELECT asset_code, category, model, organization, remarks FROM assets WHERE asset_code LIKE ? OR category LIKE ? OR model LIKE ? OR organization LIKE ? OR remarks LIKE ? ORDER BY updated_at DESC LIMIT 15");
$stmt->execute([$like, $like, $like, $like, $like]);
foreach ($stmt->fetchAll() as $row) {
    $text = implode(' ', array_filter([$row['asset_code'], $row['category'], $row['model'], $row['organization'], $row['remarks']], fn($v) => $v !== null && $v !== ''));
    $results[] = [
        'source' => 'asset',
        'source_label' => '固定资产 / Assets',
        'title' => $row['asset_code'],
        'snippet' => make_snippet($text, $q)
    ];
}

// AskMe Knowledge Base
$stmt = $pdo->prepare("SELECT content, keywords FROM askme_entries WHERE content LIKE ? OR keywords LIKE ? ORDER BY updated_at DESC");
$stmt->execute([$like, $like]);
foreach ($stmt->fetchAll() as $row) {
    $text = $row['content'];
    $results[] = [
        'source' => 'askme',
        'source_label' => 'AskMe 知识库',
        'title' => $row['keywords'],
        'snippet' => make_snippet($text, $q)
    ];
}

echo json_encode(['results' => $results]);
