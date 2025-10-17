<?php
include 'auth.php';
require_once 'member_attribute_helpers.php';

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    echo 'ZipArchive support is required to export XLSX files.';
    exit;
}

$lang = $_GET['lang'] ?? 'zh';
$headers = [
    'en' => ['Campus ID','Name','Email','Identity Number','Year of Join','Current Degree','Degree Pursuing','Phone','WeChat','Department','Workplace','Homeplace','Status'],
    'zh' => ['一卡通号','姓名','正式邮箱','身份证号','入学年份','已获学位','当前学历','手机号','微信号','所处学院/单位','工作地点','家庭住址','状态'],
];
$statusLabels = [
    'en' => ['in_work' => 'Active', 'exited' => 'Exited'],
    'zh' => ['in_work' => '在岗', 'exited' => '已离退'],
];
$customAttributes = fetch_member_attributes($pdo);
if (!empty($customAttributes)) {
    foreach ($customAttributes as $attr) {
        $headers['en'][] = $attr['label_en'] !== '' ? $attr['label_en'] : $attr['label_zh'];
        $headers['zh'][] = $attr['label_zh'] !== '' ? $attr['label_zh'] : $attr['label_en'];
    }
}
$selectedHeaders = $headers[$lang] ?? $headers['zh'];
$statusDisplay = $statusLabels[$lang] ?? $statusLabels['zh'];

$columns = ['campus_id','name','email','identity_number','year_of_join','current_degree','degree_pursuing','phone','wechat','department','workplace','homeplace','status'];

$stmt = $pdo->query('SELECT id,campus_id,name,email,identity_number,year_of_join,current_degree,degree_pursuing,phone,wechat,department,workplace,homeplace,status FROM members');
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
$memberIds = array_column($members, 'id');
$attributeValues = $memberIds ? fetch_member_attribute_map($pdo, $memberIds) : [];

$activeMembers = array_values(array_filter($members, fn($m) => ($m['status'] ?? '') === 'in_work'));
$exitedMembers = array_values(array_filter($members, fn($m) => ($m['status'] ?? '') !== 'in_work'));

sortMembersForExport($activeMembers);
sortMembersForExport($exitedMembers);

$palette = ['FFF9F2', 'F2FAFF', 'F6FFF2', 'FFF7F2', 'F2FFF8', 'F8F2FF', 'FFF5F7', 'F3F7FF'];
$styleRegistry = createStyleRegistry();

$activeSheetData = buildSheetData($activeMembers, $columns, $statusDisplay, $customAttributes, $attributeValues);
[$activeRows, $activeRowStyles] = prepareSheetRows($activeSheetData, $selectedHeaders, $palette, $styleRegistry);

$exitedSheetData = buildSheetData($exitedMembers, $columns, $statusDisplay, $customAttributes, $attributeValues);
[$exitedRows, $exitedRowStyles] = prepareSheetRows($exitedSheetData, $selectedHeaders, $palette, $styleRegistry);

$sheets = [
    ['name' => '在岗人员', 'rows' => $activeRows, 'rowStyles' => $activeRowStyles],
    ['name' => '已离退人员', 'rows' => $exitedRows, 'rowStyles' => $exitedRowStyles],
];

$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'members');
if ($tmpFile === false || $zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo 'Unable to create export file.';
    exit;
}

$zip->addFromString('[Content_Types].xml', buildContentTypesXml(count($sheets)));
$zip->addFromString('_rels/.rels', buildRootRelsXml());
$zip->addFromString('docProps/app.xml', buildAppXml(array_column($sheets, 'name')));
$zip->addFromString('docProps/core.xml', buildCoreXml());
$zip->addFromString('xl/workbook.xml', buildWorkbookXml($sheets));
$zip->addFromString('xl/_rels/workbook.xml.rels', buildWorkbookRelsXml(count($sheets)));
$zip->addFromString('xl/styles.xml', buildStylesXml($styleRegistry));

foreach ($sheets as $index => $sheet) {
    $sheetPath = 'xl/worksheets/sheet' . ($index + 1) . '.xml';
    $zip->addFromString($sheetPath, buildSheetXml($sheet['rows'], $sheet['rowStyles']));
}

$zip->close();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="members.xlsx"');
header('Cache-Control: max-age=0');
header('Content-Length: ' . filesize($tmpFile));

readfile($tmpFile);
@unlink($tmpFile);
exit;

function sortMembersForExport(array &$list): void
{
    usort($list, function ($a, $b) {
        $pursuingA = normalizeSortValue($a['degree_pursuing'] ?? '');
        $pursuingB = normalizeSortValue($b['degree_pursuing'] ?? '');
        if ($pursuingA !== $pursuingB) {
            return strcmp($pursuingA, $pursuingB);
        }

        $yearA = normalizeSortValue($a['year_of_join'] ?? '');
        $yearB = normalizeSortValue($b['year_of_join'] ?? '');
        if ($yearA !== $yearB) {
            return strcmp($yearA, $yearB);
        }

        $nameA = normalizeSortValue($a['name'] ?? '');
        $nameB = normalizeSortValue($b['name'] ?? '');
        return strcmp($nameA, $nameB);
    });
}

function normalizeSortValue($value): string
{
    if ($value === null) {
        return '';
    }
    $string = (string)$value;
    return $string;
}

function buildSheetData(array $members, array $columns, array $statusDisplay, array $attributes, array $attributeValues): array
{
    $rows = [];
    $groupKeys = [];
    foreach ($members as $member) {
        $row = [];
        foreach ($columns as $column) {
            $value = $member[$column] ?? '';
            if ($column === 'status') {
                $value = $statusDisplay[$member['status'] ?? ''] ?? ($member['status'] ?? '');
            }
            $row[] = $value === null ? '' : (string)$value;
        }
        if (!empty($attributes)) {
            $memberId = $member['id'] ?? null;
            $values = ($memberId !== null && isset($attributeValues[$memberId])) ? $attributeValues[$memberId] : [];
            foreach ($attributes as $attr) {
                $attrId = (int)$attr['id'];
                $attrValue = $values[$attrId] ?? $attr['default_value'];
                $row[] = $attrValue === null ? '' : (string)$attrValue;
            }
        }
        $rows[] = $row;
        $groupKeys[] = ($member['degree_pursuing'] ?? '') . '|' . ($member['year_of_join'] ?? '');
    }

    return ['rows' => $rows, 'groupKeys' => $groupKeys];
}

function prepareSheetRows(array $sheetData, array $headerRow, array $palette, array &$styleRegistry): array
{
    $rows = [];
    $rowStyles = [];
    $rows[] = array_map('strval', $headerRow);
    $rowStyles[] = 1; // header style

    if (!empty($sheetData['rows'])) {
        $groupStyles = assignGroupStyles($sheetData['groupKeys'], $palette, $styleRegistry);
        foreach ($sheetData['rows'] as $index => $row) {
            $rows[] = array_map(fn($value) => $value === null ? '' : (string)$value, $row);
            $rowStyles[] = $groupStyles[$sheetData['groupKeys'][$index]] ?? 0;
        }
    }

    return [$rows, $rowStyles];
}

function assignGroupStyles(array $groupKeys, array $palette, array &$styleRegistry): array
{
    $map = [];
    $paletteIndex = 0;
    if (empty($palette)) {
        $palette = ['FFFFFF'];
    }

    foreach ($groupKeys as $key) {
        if (!isset($map[$key])) {
            $color = $palette[$paletteIndex % count($palette)];
            $paletteIndex++;
            $map[$key] = ensureStyleForColor($color, $styleRegistry);
        }
    }

    return $map;
}

function createStyleRegistry(): array
{
    return [
        'fonts' => [
            '<font><sz val="11"/><name val="等线"/><family val="2"/></font>',
            '<font><b/><sz val="11"/><name val="等线"/><family val="2"/></font>',
        ],
        'fills' => [
            '<fill><patternFill patternType="none"/></fill>',
            '<fill><patternFill patternType="gray125"/></fill>',
        ],
        'borders' => [
            '<border><left/><right/><top/><bottom/><diagonal/></border>',
        ],
        'cellStyleXfs' => [
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>',
        ],
        'cellXfs' => [
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>',
            '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>',
        ],
        'fillMap' => [],
        'colorStyleMap' => [],
    ];
}

function ensureStyleForColor(string $hexColor, array &$registry): int
{
    $hexColor = strtoupper(ltrim($hexColor, '#'));
    if (strlen($hexColor) === 6) {
        $argb = 'FF' . $hexColor;
    } elseif (strlen($hexColor) === 8) {
        $argb = $hexColor;
    } else {
        $argb = 'FFFFFFFF';
    }

    if (!isset($registry['colorStyleMap'][$argb])) {
        if (!isset($registry['fillMap'][$argb])) {
            $registry['fills'][] = '<fill><patternFill patternType="solid"><fgColor rgb="' . $argb . '"/><bgColor indexed="64"/></patternFill></fill>';
            $registry['fillMap'][$argb] = count($registry['fills']) - 1;
        }
        $fillId = $registry['fillMap'][$argb];
        $registry['cellXfs'][] = '<xf numFmtId="0" fontId="0" fillId="' . $fillId . '" borderId="0" xfId="0" applyFill="1"/>';
        $registry['colorStyleMap'][$argb] = count($registry['cellXfs']) - 1;
    }

    return $registry['colorStyleMap'][$argb];
}

function buildSheetXml(array $rows, array $rowStyles): string
{
    $rowCount = count($rows);
    $colCount = $rowCount ? count($rows[0]) : 0;
    $dimension = $colCount ? 'A1:' . columnLetter($colCount) . $rowCount : 'A1';

    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $xml .= '<dimension ref="' . $dimension . '"/>';
    $xml .= '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';
    $xml .= '<sheetFormatPr defaultRowHeight="15"/>';
    $xml .= '<sheetData>';

    foreach ($rows as $rowIndex => $cells) {
        $rowNumber = $rowIndex + 1;
        $xml .= '<row r="' . $rowNumber . '">';
        foreach ($cells as $cellIndex => $value) {
            $value = $value === null ? '' : (string)$value;
            $column = columnLetter($cellIndex + 1);
            $cellReference = $column . $rowNumber;
            $styleId = $rowStyles[$rowIndex] ?? 0;
            $styleAttr = $styleId ? ' s="' . $styleId . '"' : '';
            $needsPreserve = preg_match('/^\s|\s$/u', $value) === 1;
            $spaceAttr = $needsPreserve ? ' xml:space="preserve"' : '';
            $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $xml .= '<c r="' . $cellReference . '" t="inlineStr"' . $styleAttr . '><is><t' . $spaceAttr . '>' . $escaped . '</t></is></c>';
        }
        $xml .= '</row>';
    }

    $xml .= '</sheetData>';
    $xml .= '</worksheet>';

    return $xml;
}

function columnLetter(int $index): string
{
    $letters = '';
    while ($index > 0) {
        $index--;
        $letters = chr(($index % 26) + 65) . $letters;
        $index = intdiv($index, 26);
    }
    return $letters ?: 'A';
}

function buildStylesXml(array $registry): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $xml .= '<fonts count="' . count($registry['fonts']) . '">' . implode('', $registry['fonts']) . '</fonts>';
    $xml .= '<fills count="' . count($registry['fills']) . '">' . implode('', $registry['fills']) . '</fills>';
    $xml .= '<borders count="' . count($registry['borders']) . '">' . implode('', $registry['borders']) . '</borders>';
    $xml .= '<cellStyleXfs count="' . count($registry['cellStyleXfs']) . '">' . implode('', $registry['cellStyleXfs']) . '</cellStyleXfs>';
    $xml .= '<cellXfs count="' . count($registry['cellXfs']) . '">' . implode('', $registry['cellXfs']) . '</cellXfs>';
    $xml .= '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>';
    $xml .= '</styleSheet>';
    return $xml;
}

function buildContentTypesXml(int $sheetCount): string
{
    $overrides = [
        '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>',
        '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>',
        '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>',
        '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>',
    ];

    for ($i = 1; $i <= $sheetCount; $i++) {
        $overrides[] = '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
    }

    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
    $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
    $xml .= '<Default Extension="xml" ContentType="application/xml"/>';
    $xml .= implode('', $overrides);
    $xml .= '</Types>';
    return $xml;
}

function buildRootRelsXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
}

function buildWorkbookXml(array $sheets): string
{
    $sheetXml = '';
    foreach ($sheets as $index => $sheet) {
        $name = htmlspecialchars($sheet['name'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $sheetXml .= '<sheet name="' . $name . '" sheetId="' . ($index + 1) . '" r:id="rId' . ($index + 1) . '"/>';
    }

    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $xml .= '<bookViews><workbookView/></bookViews>';
    $xml .= '<sheets>' . $sheetXml . '</sheets>';
    $xml .= '</workbook>';
    return $xml;
}

function buildWorkbookRelsXml(int $sheetCount): string
{
    $rels = '';
    for ($i = 1; $i <= $sheetCount; $i++) {
        $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
    }
    $rels .= '<Relationship Id="rId' . ($sheetCount + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
}

function buildAppXml(array $sheetNames): string
{
    $vectorEntries = '';
    foreach ($sheetNames as $name) {
        $vectorEntries .= '<vt:lpstr>' . htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</vt:lpstr>';
    }
    $count = count($sheetNames);

    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">';
    $xml .= '<Application>Team Management Platform</Application>';
    $xml .= '<DocSecurity>0</DocSecurity>';
    $xml .= '<ScaleCrop>false</ScaleCrop>';
    $xml .= '<HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant><vt:variant><vt:i4>' . $count . '</vt:i4></vt:variant></vt:vector></HeadingPairs>';
    $xml .= '<TitlesOfParts><vt:vector size="' . $count . '" baseType="lpstr">' . $vectorEntries . '</vt:vector></TitlesOfParts>';
    $xml .= '</Properties>';
    return $xml;
}

function buildCoreXml(): string
{
    $timestamp = gmdate('Y-m-d\TH:i:s\Z');
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>Team Management Platform</dc:creator><cp:lastModifiedBy>Team Management Platform</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:modified></cp:coreProperties>';
}
