<?php
include 'auth.php';
require_once 'config.php';
require_once 'publish_helpers.php';

if (($_SESSION['role'] ?? '') !== 'manager') {
    http_response_code(403);
    exit('Forbidden');
}

$attributes = getPublishAttributes($pdo);
$attributeMap = [];
$fileAttributeIds = [];
foreach ($attributes as $attr) {
    $attrId = (int)($attr['id'] ?? 0);
    if ($attrId <= 0) {
        continue;
    }
    $attributeMap[$attrId] = $attr;
    if (($attr['attribute_type'] ?? '') === 'file') {
        $fileAttributeIds[] = $attrId;
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

$rawFilters = $_GET['filters'] ?? [];
$activeFilters = [];
if (is_array($rawFilters)) {
    foreach ($rawFilters as $filterId => $filterValue) {
        $attrId = (int)$filterId;
        if ($attrId <= 0 || !isset($attributeMap[$attrId])) {
            continue;
        }
        if (is_array($filterValue)) {
            continue;
        }
        $filterValue = trim((string)$filterValue);
        if ($filterValue === '') {
            continue;
        }
        $activeFilters[$attrId] = $filterValue;
    }
}

$filteredEntries = [];
if (!empty($activeFilters)) {
    foreach ($entries as $entry) {
        $entryId = (int)($entry['id'] ?? 0);
        $matches = true;
        foreach ($activeFilters as $attrId => $filterValue) {
            $attr = $attributeMap[$attrId];
            $attrType = $attr['attribute_type'] ?? 'text';
            $value = (string)($valuesMap[$entryId][$attrId] ?? ($attrType === 'file' ? '' : (string)($attr['default_value'] ?? '')));
            if ($attrType === 'file') {
                if ($filterValue === 'has' && $value === '') {
                    $matches = false;
                    break;
                }
                if ($filterValue === 'empty' && $value !== '') {
                    $matches = false;
                    break;
                }
            } elseif ($attrType === 'select' || $attrType === 'date') {
                if ($value !== $filterValue) {
                    $matches = false;
                    break;
                }
            } elseif (stripos($value, $filterValue) === false) {
                $matches = false;
                break;
            }
        }
        if ($matches) {
            $filteredEntries[] = $entry;
        }
    }
} else {
    $filteredEntries = $entries;
}

$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'publish_zip_');
if ($tmpFile === false || $zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('Unable to create ZIP.');
}

$added = false;
foreach ($filteredEntries as $entry) {
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
