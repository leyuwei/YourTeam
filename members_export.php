<?php
include 'auth.php';
require_once 'member_extra_helpers.php';

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
$extraAttributes = getMemberExtraAttributes($pdo);
$extraHeadersEn = array_map(function ($attr) {
    $id = (int)($attr['id'] ?? 0);
    $nameEn = trim((string)($attr['name_en'] ?? ''));
    $nameZh = trim((string)($attr['name_zh'] ?? ''));
    if ($nameEn !== '') {
        return $nameEn;
    }
    if ($nameZh !== '') {
        return $nameZh;
    }
    return 'Attribute ' . $id;
}, $extraAttributes);
$extraHeadersZh = array_map(function ($attr) {
    $id = (int)($attr['id'] ?? 0);
    $nameZh = trim((string)($attr['name_zh'] ?? ''));
    $nameEn = trim((string)($attr['name_en'] ?? ''));
    if ($nameZh !== '') {
        return $nameZh;
    }
    if ($nameEn !== '') {
        return $nameEn;
    }
    return '属性' . $id;
}, $extraAttributes);
$selectedHeaders = $headers[$lang] ?? $headers['zh'];
$statusDisplay = $statusLabels[$lang] ?? $statusLabels['zh'];
$selectedHeaders = array_merge($selectedHeaders, $lang === 'en' ? $extraHeadersEn : $extraHeadersZh);

$columns = [];
$columnMetas = [];
$baseColumns = ['campus_id','name','email','identity_number','year_of_join','current_degree','degree_pursuing','phone','wechat','department','workplace','homeplace','status'];
foreach ($baseColumns as $col) {
    $columns[] = $col;
    $columnMetas[] = ['type' => 'text'];
}
foreach ($extraAttributes as $attr) {
    $columns[] = 'extra_' . (int)($attr['id'] ?? 0);
    $columnMetas[] = [
        'type' => in_array($attr['attribute_type'] ?? '', ['text', 'media'], true) ? $attr['attribute_type'] : 'text',
        'attrId' => (int)($attr['id'] ?? 0),
    ];
}

$stmt = $pdo->query('SELECT id,campus_id,name,email,identity_number,year_of_join,current_degree,degree_pursuing,phone,wechat,department,workplace,homeplace,status FROM members');
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
$memberIds = array_column($members, 'id');
$extraValuesMap = !empty($memberIds) ? getMemberExtraValues($pdo, $memberIds) : [];
foreach ($members as &$member) {
    $memberId = (int)($member['id'] ?? 0);
    foreach ($extraAttributes as $attr) {
        $attrId = (int)($attr['id'] ?? 0);
        $key = 'extra_' . $attrId;
        $attrType = in_array($attr['attribute_type'] ?? '', ['text', 'media'], true) ? $attr['attribute_type'] : 'text';
        $fallback = $attrType === 'text' ? (string)($attr['default_value'] ?? '') : '';
        $member[$key] = $extraValuesMap[$memberId][$attrId] ?? $fallback;
    }
}
unset($member);

$mediaIndex = 1;
$imageContentTypes = [];
$activeImages = [];
$exitedImages = [];

$activeMembers = array_values(array_filter($members, fn($m) => ($m['status'] ?? '') === 'in_work'));
$exitedMembers = array_values(array_filter($members, fn($m) => ($m['status'] ?? '') !== 'in_work'));

sortMembersForExport($activeMembers);
sortMembersForExport($exitedMembers);

$activeImages = assignImageResources(collectSheetImages($activeMembers, $columns, $columnMetas, 2), $mediaIndex, $imageContentTypes);
$exitedImages = assignImageResources(collectSheetImages($exitedMembers, $columns, $columnMetas, 2), $mediaIndex, $imageContentTypes);

$palette = ['FFF9F2', 'F2FAFF', 'F6FFF2', 'FFF7F2', 'F2FFF8', 'F8F2FF', 'FFF5F7', 'F3F7FF'];
$styleRegistry = createStyleRegistry();

$activeSheetData = buildSheetData($activeMembers, $columns, $statusDisplay);
[$activeRows, $activeRowStyles] = prepareSheetRows($activeSheetData, $selectedHeaders, $palette, $styleRegistry);

$exitedSheetData = buildSheetData($exitedMembers, $columns, $statusDisplay);
[$exitedRows, $exitedRowStyles] = prepareSheetRows($exitedSheetData, $selectedHeaders, $palette, $styleRegistry);

$sheets = [
    ['name' => '在岗人员', 'rows' => $activeRows, 'rowStyles' => $activeRowStyles, 'images' => $activeImages],
    ['name' => '已离退人员', 'rows' => $exitedRows, 'rowStyles' => $exitedRowStyles, 'images' => $exitedImages],
];

foreach ($sheets as $index => &$sheet) {
    if (!empty($sheet['images'])) {
        $sheet['drawingPath'] = 'xl/drawings/drawing' . ($index + 1) . '.xml';
        $sheet['drawingRelsPath'] = 'xl/drawings/_rels/drawing' . ($index + 1) . '.xml.rels';
        $sheet['sheetRelsPath'] = 'xl/worksheets/_rels/sheet' . ($index + 1) . '.xml.rels';
        $sheet['drawingRelId'] = 'rId1';
    }
}
unset($sheet);

$drawingParts = array_values(array_map(fn($s) => $s['drawingPath'] ?? null, $sheets));
$drawingParts = array_filter($drawingParts);

$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'members');
if ($tmpFile === false || $zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo 'Unable to create export file.';
    exit;
}

$zip->addFromString('[Content_Types].xml', buildContentTypesXml(count($sheets), $drawingParts, $imageContentTypes));
$zip->addFromString('_rels/.rels', buildRootRelsXml());
$zip->addFromString('docProps/app.xml', buildAppXml(array_column($sheets, 'name')));
$zip->addFromString('docProps/core.xml', buildCoreXml());
$zip->addFromString('xl/workbook.xml', buildWorkbookXml($sheets));
$zip->addFromString('xl/_rels/workbook.xml.rels', buildWorkbookRelsXml(count($sheets)));
$zip->addFromString('xl/styles.xml', buildStylesXml($styleRegistry));

foreach ($sheets as $index => $sheet) {
    $sheetPath = 'xl/worksheets/sheet' . ($index + 1) . '.xml';
    $drawingRelId = $sheet['drawingRelId'] ?? null;
    $zip->addFromString($sheetPath, buildSheetXml($sheet['rows'], $sheet['rowStyles'], $drawingRelId));

    if (!empty($sheet['images'])) {
        $zip->addFromString($sheet['sheetRelsPath'], buildSheetRelsXml('../drawings/drawing' . ($index + 1) . '.xml', $drawingRelId));
        $zip->addFromString($sheet['drawingPath'], buildDrawingXml($sheet['images']));
        $zip->addFromString($sheet['drawingRelsPath'], buildDrawingRelsXml($sheet['images']));
        foreach ($sheet['images'] as $image) {
            $content = @file_get_contents($image['path']);
            if ($content !== false) {
                $zip->addFromString($image['zipPath'], $content);
            }
        }
    }
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

function buildSheetData(array $members, array $columns, array $statusDisplay): array
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

function buildSheetXml(array $rows, array $rowStyles, ?string $drawingRelId = null): string
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
    if ($drawingRelId) {
        $xml .= '<drawing r:id="' . htmlspecialchars($drawingRelId, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '"/>';
    }
    $xml .= '</worksheet>';

    return $xml;
}

function buildSheetRelsXml(string $drawingTarget, string $drawingRelId): string
{
    $drawingTarget = htmlspecialchars($drawingTarget, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $drawingRelId = htmlspecialchars($drawingRelId, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="' . $drawingRelId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="' . $drawingTarget . '"/></Relationships>';
}

function buildDrawingXml(array $images): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';

    foreach ($images as $index => $image) {
        $col = max(0, (int)($image['col'] ?? 1) - 1);
        $row = max(0, (int)($image['row'] ?? 1) - 1);
        $cx = (int)($image['cx'] ?? 190000);
        $cy = (int)($image['cy'] ?? 190000);
        $relId = 'rId' . ($index + 1);

        $xml .= '<xdr:oneCellAnchor>';
        $xml .= '<xdr:from><xdr:col>' . $col . '</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>' . $row . '</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>';
        $xml .= '<xdr:ext cx="' . $cx . '" cy="' . $cy . '"/>';
        $xml .= '<xdr:pic>';
        $xml .= '<xdr:nvPicPr><xdr:cNvPr id="' . ($index + 1) . '" name="Picture ' . ($index + 1) . '"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr>';
        $xml .= '<xdr:blipFill><a:blip r:embed="' . $relId . '"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>';
        $xml .= '<xdr:spPr><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr>';
        $xml .= '</xdr:pic><xdr:clientData/>';
        $xml .= '</xdr:oneCellAnchor>';
    }

    $xml .= '</xdr:wsDr>';
    return $xml;
}

function buildDrawingRelsXml(array $images): string
{
    $rels = '';
    foreach ($images as $index => $image) {
        $target = '../media/' . basename((string)($image['zipPath'] ?? ''));
        $rels .= '<Relationship Id="rId' . ($index + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="' . htmlspecialchars($target, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '"/>';
    }

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
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

function buildContentTypesXml(int $sheetCount, array $drawingParts = [], array $imageContentTypes = []): string
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

    foreach ($drawingParts as $drawing) {
        $overrides[] = '<Override PartName="/' . ltrim($drawing, '/') . '" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
    }

    $defaultTypes = [
        'rels' => 'application/vnd.openxmlformats-package.relationships+xml',
        'xml' => 'application/xml',
    ];

    foreach ($imageContentTypes as $ext => $type) {
        $ext = strtolower(trim((string)$ext));
        if ($ext !== '' && $type) {
            $defaultTypes[$ext] = $type;
        }
    }

    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
    foreach ($defaultTypes as $ext => $type) {
        $xml .= '<Default Extension="' . htmlspecialchars($ext, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" ContentType="' . htmlspecialchars($type, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '"/>';
    }
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

function collectSheetImages(array $members, array $columns, array $columnMetas, int $startRow): array
{
    $images = [];
    foreach ($members as $rowIndex => $member) {
        foreach ($columns as $colIndex => $column) {
            $meta = $columnMetas[$colIndex] ?? ['type' => 'text'];
            if (($meta['type'] ?? 'text') !== 'media') {
                continue;
            }

            $value = $member[$column] ?? '';
            if ($value === '') {
                continue;
            }
            $fullPath = __DIR__ . '/' . ltrim((string)$value, '/');
            $info = normalizeImageInfo($fullPath);
            if ($info === null) {
                continue;
            }

            $heightEmu = 190000;
            $ratio = ($info['height'] ?? 0) > 0 ? ($info['width'] / $info['height']) : 1;
            $cx = (int)max(95000, round($heightEmu * $ratio));
            $cy = $heightEmu;

            $images[] = [
                'row' => $startRow + $rowIndex,
                'col' => $colIndex + 1,
                'path' => $fullPath,
                'mime' => $info['mime'],
                'extension' => $info['extension'],
                'cx' => $cx,
                'cy' => $cy,
            ];
        }
    }

    return $images;
}

function assignImageResources(array $images, int &$counter, array &$contentTypes): array
{
    foreach ($images as &$image) {
        $ext = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string)($image['extension'] ?? 'png')));
        $ext = $ext !== '' ? $ext : 'png';
        $image['extension'] = $ext;
        $contentTypes[$ext] = $image['mime'] ?? 'image/' . $ext;
        $image['zipPath'] = 'xl/media/image' . $counter . '.' . $ext;
        $counter++;
    }
    unset($image);
    return $images;
}

function normalizeImageInfo(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }

    $details = @getimagesize($path);
    if ($details === false || empty($details['mime'])) {
        return null;
    }

    $mime = (string)$details['mime'];
    $extension = '';
    if (!empty($details[2])) {
        $extension = ltrim((string)image_type_to_extension((int)$details[2], false), '.');
    }

    if ($extension === '') {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/webp' => 'webp',
        ];
        $extension = $mimeMap[$mime] ?? '';
    }

    if ($extension === '') {
        $extension = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
    }

    return [
        'mime' => $mime,
        'extension' => $extension !== '' ? $extension : 'png',
        'width' => (int)($details[0] ?? 0),
        'height' => (int)($details[1] ?? 0),
    ];
}
