<?php
include 'header.php';
$user_id = $_SESSION['role']==='manager' ? $_SESSION['manager_id'] : $_SESSION['member_id'];
$role = $_SESSION['role'];
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');
$requested_export = isset($_GET['export']) && $_GET['export'] === 'xlsx';
// initialize records and stats per category
$records = ['work'=>[], 'personal'=>[], 'longterm'=>[]];
$stats = ['work'=>['done'=>0,'total'=>0], 'personal'=>['done'=>0,'total'=>0], 'longterm'=>['done'=>0,'total'=>0]];
$cat_labels = ['work'=>'工作','personal'=>'私人','longterm'=>'长期'];
$weekday_fallback = ['mon'=>'周一','tue'=>'周二','wed'=>'周三','thu'=>'周四','fri'=>'周五','sat'=>'周六','sun'=>'周日'];
if(!empty($_GET['start']) && !empty($_GET['end'])){
    $expr = "DATE_ADD(week_start, INTERVAL CASE day WHEN 'mon' THEN 0 WHEN 'tue' THEN 1 WHEN 'wed' THEN 2 WHEN 'thu' THEN 3 WHEN 'fri' THEN 4 WHEN 'sat' THEN 5 WHEN 'sun' THEN 6 ELSE 0 END DAY)";
    $sql = "SELECT *, $expr AS item_date FROM todolist_items WHERE user_id=? AND user_role=? AND $expr BETWEEN ? AND ? ORDER BY item_date DESC, sort_order DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id,$role,$start,$end]);
    foreach($stmt as $row){
        $cat = $row['category'];
        if(isset($records[$cat])){
            $itemDate = $row['item_date'] ?? $row['week_start'];
            try {
                $dateObj = new DateTime($itemDate ?? $row['week_start']);
                $formattedDate = $dateObj->format('Y-m-d');
                $weekdayKey = $row['day'] ?? strtolower($dateObj->format('D'));
            } catch (Exception $e) {
                $formattedDate = $row['item_date'] ?? $row['week_start'];
                $weekdayKey = $row['day'] ?? 'mon';
            }
            $row['item_date'] = $itemDate;
            $row['item_date_formatted'] = $formattedDate;
            $row['weekday_key'] = in_array($weekdayKey, ['mon','tue','wed','thu','fri','sat','sun'], true) ? $weekdayKey : 'mon';
            $row['weekday_label'] = $weekday_fallback[$row['weekday_key']] ?? $row['weekday_key'];
            $records[$cat][] = $row;
            $stats[$cat]['total']++;
            if($row['is_done']) $stats[$cat]['done']++;
        }
    }
    foreach($records as &$rows){
        usort($rows, function($a,$b){
            $dateComparison = strcmp($b['item_date_formatted'], $a['item_date_formatted']);
            if($dateComparison !== 0){
                return $dateComparison;
            }
            return ((int)($b['sort_order'] ?? 0)) <=> ((int)($a['sort_order'] ?? 0));
        });
    }
    unset($rows);
}
$total_all = $stats['work']['total'] + $stats['personal']['total'] + $stats['longterm']['total'];
$prompt_params_json = json_encode([
    'start' => $start,
    'end' => $end,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

if($requested_export){
    if(!class_exists('ZipArchive')){
        http_response_code(500);
        echo 'ZipArchive extension is required to export Excel files.';
        exit;
    }
    $lang = $_GET['lang'] ?? 'zh';
    $lang = $lang === 'en' ? 'en' : 'zh';
    $categoryTitles = [
        'en' => ['work' => 'Work', 'personal' => 'Personal', 'longterm' => 'Long Term'],
        'zh' => ['work' => '工作', 'personal' => '私人', 'longterm' => '长期'],
    ];
    $weekdayLabels = [
        'en' => ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'],
        'zh' => $weekday_fallback,
    ];
    $statusLabels = [
        'en' => ['done' => 'Completed', 'todo' => 'Pending'],
        'zh' => ['done' => '已完成', 'todo' => '未完成'],
    ];
    $headers = [
        'en' => ['Category', 'Date', 'Weekday', 'Item', 'Status', 'Progress'],
        'zh' => ['分类', '日期', '周几', '事项', '状态', '进度'],
    ];
    $rangeLabel = $lang === 'en' ? 'Date Range' : '统计范围';
    $noItemsLabel = $lang === 'en' ? 'No todo items in this category' : '该分类暂无待办事项';
    $sheetNames = ['en' => 'Todo Assessment', 'zh' => '待办统计'];
    $overallEmptyLabel = $lang === 'en' ? 'No todo items' : '暂无待办事项';
    $rows = [];
    $rows[] = [$rangeLabel, sprintf('%s - %s', $start, $end)];
    $columnCount = count($headers[$lang]);
    $rows[] = array_fill(0, $columnCount, '');
    $rows[] = $headers[$lang];
    if($total_all>0){
        foreach(['work','personal','longterm'] as $cat){
            $catTitle = $categoryTitles[$lang][$cat] ?? $cat;
            $progress = sprintf('%d/%d', $stats[$cat]['done'], $stats[$cat]['total']);
            if(!empty($records[$cat])){
                foreach($records[$cat] as $index => $item){
                    $badgeDate = $item['item_date_formatted'] ?? ($item['item_date'] ?? '');
                    $weekdayKey = $item['weekday_key'] ?? 'mon';
                    $weekdayText = $weekdayLabels[$lang][$weekdayKey] ?? $weekdayKey;
                    $statusKey = $item['is_done'] ? 'done' : 'todo';
                    $statusText = $statusLabels[$lang][$statusKey] ?? $statusKey;
                    $rows[] = [
                        $catTitle,
                        $badgeDate,
                        $weekdayText,
                        (string)($item['content'] ?? ''),
                        $statusText,
                        $index === 0 ? $progress : ''
                    ];
                }
            } else {
                $rows[] = [$catTitle, '', '', $noItemsLabel, '', $progress];
            }
            $rows[] = array_fill(0, $columnCount, '');
        }
    } else {
        $rows[] = array_fill(0, $columnCount, '');
        $rows[] = ['', '', '', $overallEmptyLabel, '', ''];
    }

    $sheetName = $sheetNames[$lang] ?? 'Assessment';
    try {
        $tmpFile = buildAssessmentXlsx($rows, $sheetName);
    } catch (RuntimeException $e) {
        http_response_code(500);
        echo 'Unable to generate export file.';
        exit;
    }
    $filenameBase = sprintf('todolist_%s_%s.xlsx', $start, $end);
    $safeFilename = preg_replace('/[^A-Za-z0-9\-_.]/', '_', $filenameBase);
    if($safeFilename === '' || $safeFilename === null){
        $safeFilename = 'todolist.xlsx';
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $safeFilename . '"; filename*=UTF-8\'\'' . rawurlencode($filenameBase));
    header('Cache-Control: max-age=0');
    header('Content-Length: ' . filesize($tmpFile));
    readfile($tmpFile);
    @unlink($tmpFile);
    exit;
}

function buildAssessmentXlsx(array $rows, string $sheetName): string
{
    $timestamp = gmdate('Y-m-d\TH:i:s\Z');
    $normalizedSheetName = sanitizeAssessmentSheetName($sheetName);
    $columnCount = 0;
    foreach($rows as &$row){
        $row = array_map(function($value){
            if($value === null){
                return '';
            }
            if(is_bool($value)){
                return $value ? 'TRUE' : 'FALSE';
            }
            if(is_scalar($value)){
                return (string)$value;
            }
            return '';
        }, $row);
        $columnCount = max($columnCount, count($row));
    }
    unset($row);
    if($columnCount === 0){
        $columnCount = 1;
        $rows = [['']];
    }
    foreach($rows as &$row){
        $row = array_pad($row, $columnCount, '');
    }
    unset($row);

    $sheetXml = buildAssessmentSheetXml($rows);
    $workbookXml = buildAssessmentWorkbookXml($normalizedSheetName);
    $workbookRelsXml = buildAssessmentWorkbookRelsXml();
    $contentTypesXml = buildAssessmentContentTypesXml();
    $rootRelsXml = buildAssessmentRootRelsXml();
    $stylesXml = buildAssessmentStylesXml();
    $appXml = buildAssessmentAppXml($normalizedSheetName);
    $coreXml = buildAssessmentCoreXml($timestamp);

    $tmpFile = tempnam(sys_get_temp_dir(), 'assessment');
    if($tmpFile === false){
        throw new RuntimeException('Unable to allocate temporary file.');
    }
    $zip = new ZipArchive();
    if($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true){
        @unlink($tmpFile);
        throw new RuntimeException('Unable to create XLSX archive.');
    }

    $zip->addFromString('[Content_Types].xml', $contentTypesXml);
    $zip->addFromString('_rels/.rels', $rootRelsXml);
    $zip->addFromString('docProps/app.xml', $appXml);
    $zip->addFromString('docProps/core.xml', $coreXml);
    $zip->addFromString('xl/workbook.xml', $workbookXml);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);
    $zip->addFromString('xl/styles.xml', $stylesXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

    $zip->close();

    return $tmpFile;
}

function buildAssessmentSheetXml(array $rows): string
{
    $rowCount = count($rows);
    $columnCount = $rowCount > 0 ? count($rows[0]) : 0;
    if($columnCount === 0){
        $columnCount = 1;
        $rows = [['']];
        $rowCount = 1;
    }
    $maxColumn = assessmentColumnName($columnCount);
    $dimension = 'A1:' . $maxColumn . $rowCount;
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $xml .= '<dimension ref="' . $dimension . '"/>';
    $xml .= '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';
    $xml .= '<sheetFormatPr defaultRowHeight="15"/>';
    $xml .= '<sheetData>';
    foreach($rows as $rowIndex => $row){
        $rowNumber = $rowIndex + 1;
        $xml .= '<row r="' . $rowNumber . '">';
        foreach($row as $columnIndex => $cellValue){
            $cellRef = assessmentColumnName($columnIndex + 1) . $rowNumber;
            $escaped = assessmentEscapeXml((string)$cellValue);
            $xml .= '<c r="' . $cellRef . '" t="inlineStr"><is><t xml:space="preserve">' . $escaped . '</t></is></c>';
        }
        $xml .= '</row>';
    }
    $xml .= '</sheetData>';
    $xml .= '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>';
    $xml .= '</worksheet>';
    return $xml;
}

function assessmentEscapeXml(string $value): string
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function assessmentColumnName(int $index): string
{
    $name = '';
    while($index > 0){
        $index--;
        $name = chr(($index % 26) + 65) . $name;
        $index = intdiv($index, 26);
    }
    return $name === '' ? 'A' : $name;
}

function sanitizeAssessmentSheetName(string $name): string
{
    $name = preg_replace('/[\[\]:\\\/?*]/u', ' ', $name);
    $name = trim($name);
    if(function_exists('mb_substr')){
        $name = mb_substr($name, 0, 31, 'UTF-8');
    } else {
        $name = substr($name, 0, 31);
    }
    if($name === ''){
        $name = 'Sheet1';
    }
    return $name;
}

function buildAssessmentWorkbookXml(string $sheetName): string
{
    $escaped = assessmentEscapeXml($sheetName);
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $xml .= '<fileVersion appName="Calc"/>';
    $xml .= '<sheets>';
    $xml .= '<sheet name="' . $escaped . '" sheetId="1" r:id="rId1"/>';
    $xml .= '</sheets>';
    $xml .= '</workbook>';
    return $xml;
}

function buildAssessmentWorkbookRelsXml(): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>';
    $xml .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
    $xml .= '</Relationships>';
    return $xml;
}

function buildAssessmentContentTypesXml(): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
    $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
    $xml .= '<Default Extension="xml" ContentType="application/xml"/>';
    $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
    $xml .= '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
    $xml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
    $xml .= '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>';
    $xml .= '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';
    $xml .= '</Types>';
    return $xml;
}

function buildAssessmentRootRelsXml(): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
    $xml .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>';
    $xml .= '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>';
    $xml .= '</Relationships>';
    return $xml;
}

function buildAssessmentStylesXml(): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $xml .= '<fonts count="1"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font></fonts>';
    $xml .= '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>';
    $xml .= '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>';
    $xml .= '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';
    $xml .= '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/></cellXfs>';
    $xml .= '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>';
    $xml .= '</styleSheet>';
    return $xml;
}

function buildAssessmentAppXml(string $sheetName): string
{
    $escaped = assessmentEscapeXml($sheetName);
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">';
    $xml .= '<Application>PHP</Application>';
    $xml .= '<HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant><vt:variant><vt:i4>1</vt:i4></vt:variant></vt:vector></HeadingPairs>';
    $xml .= '<TitlesOfParts><vt:vector size="1" baseType="lpstr"><vt:lpstr>' . $escaped . '</vt:lpstr></vt:vector></TitlesOfParts>';
    $xml .= '</Properties>';
    return $xml;
}

function buildAssessmentCoreXml(string $timestamp): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
    $xml .= '<dc:creator>YourTeam</dc:creator>';
    $xml .= '<cp:lastModifiedBy>YourTeam</cp:lastModifiedBy>';
    $xml .= '<dcterms:created xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:created>';
    $xml .= '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:modified>';
    $xml .= '</cp:coreProperties>';
    return $xml;
}
?>
<h2 class="text-center"><span data-i18n="todolist.assessment">待办统计</span></h2>
<form method="get" class="mb-3 d-flex flex-wrap align-items-center gap-2" id="assessmentFilterForm">
  <input type="date" name="start" value="<?= htmlspecialchars($start); ?>" class="form-control w-auto">
  <input type="date" name="end" value="<?= htmlspecialchars($end); ?>" class="form-control w-auto">
  <button type="submit" class="btn btn-primary" data-i18n="todolist.assessment.generate">统计</button>
  <button type="button" class="btn btn-outline-secondary" id="exportAssessment" data-i18n="todolist.assessment.export_excel">导出Excel</button>
  <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#assessmentPromptModal" data-i18n="todolist.assessment.prompts.open">AI 提示词</button>
</form>
<?php if($total_all>0): ?>
  <?php foreach(['work','personal','longterm'] as $cat): ?>
    <h4><span data-i18n="todolist.category.<?= $cat ?>"><?= $cat_labels[$cat]; ?></span> <small>(<?= $stats[$cat]['done']; ?>/<?= $stats[$cat]['total']; ?>)</small></h4>
    <?php if($stats[$cat]['total']>0): ?>
    <ul class="list-group mb-3">
      <?php foreach($records[$cat] as $r): ?>
      <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
          <span class="badge rounded-pill text-bg-info px-3 py-2">
            <span class="badge-date fw-semibold"><?= htmlspecialchars($r['item_date_formatted'] ?? ($r['item_date'] ?? '')); ?></span>
            <span class="mx-1">·</span>
            <span class="badge-day" data-i18n="todolist.days.<?= htmlspecialchars($r['weekday_key']); ?>"><?= htmlspecialchars($r['weekday_label']); ?></span>
          </span>
          <span class="todo-content"><?= htmlspecialchars($r['content']); ?></span>
        </div>
        <span class="status-indicator" aria-label="<?= $r['is_done'] ? '已完成' : '未完成'; ?>" data-i18n-title="todolist.assessment.status.<?= $r['is_done'] ? 'done' : 'todo'; ?>" title="<?= $r['is_done'] ? '已完成' : '未完成'; ?>"><?= $r['is_done'] ? '✅' : '❌'; ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="text-muted" data-i18n="todolist.assessment.no_items">无待办事项</p>
    <?php endif; ?>
  <?php endforeach; ?>
<?php else: ?>
  <p class="text-muted" data-i18n="todolist.assessment.no_items">无待办事项</p>
<?php endif; ?>
<div class="modal fade" id="assessmentPromptModal" tabindex="-1" aria-labelledby="assessmentPromptModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="assessmentPromptModalLabel" data-i18n="todolist.assessment.prompts.title">AI 提示词备选</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭" data-i18n-attr="aria-label:todolist.assessment.prompts.close"></button>
      </div>
      <div class="modal-body">
        <span class="badge rounded-pill text-bg-light text-secondary mb-3" data-i18n="todolist.assessment.prompts.helper_badge">AI 助手</span>
        <p class="text-muted" data-i18n="todolist.assessment.prompts.description" data-i18n-params='<?= $prompt_params_json; ?>'>请将以下提示词复制到你的 AI 工具中，帮助其总结在所选日期范围内三大类事项的重点。</p>
        <div class="list-group">
          <div class="list-group-item">
            <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-start">
              <div class="prompt-text small flex-grow-1" style="white-space: pre-line;" id="prompt-text-1" data-i18n="todolist.assessment.prompts.item1" data-i18n-params='<?= $prompt_params_json; ?>'>请扮演专业周报整理助手，基于我在所选日期范围内（<?= htmlspecialchars($start); ?> 至 <?= htmlspecialchars($end); ?>）记录的待办事项，将“工作”“私人”“长期”三类里的高价值事件逐条总结，注意不同描述下可能是同一件事，请进行关联归纳。</div>
              <button type="button" class="btn btn-outline-primary btn-sm copy-prompt align-self-lg-start" data-target="prompt-text-1" data-i18n="todolist.assessment.prompts.copy">复制提示词</button>
            </div>
          </div>
          <div class="list-group-item">
            <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-start">
              <div class="prompt-text small flex-grow-1" style="white-space: pre-line;" id="prompt-text-2" data-i18n="todolist.assessment.prompts.item2" data-i18n-params='<?= $prompt_params_json; ?>'>请帮我对<?= htmlspecialchars($start); ?> 至 <?= htmlspecialchars($end); ?>期间的待办事项做复盘，分“工作”“私人”“长期”总结关键成果，识别重复描述的同一事务并合并成统一条目，清楚列出每条结论。</div>
              <button type="button" class="btn btn-outline-primary btn-sm copy-prompt align-self-lg-start" data-target="prompt-text-2" data-i18n="todolist.assessment.prompts.copy">复制提示词</button>
            </div>
          </div>
          <div class="list-group-item">
            <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-start">
              <div class="prompt-text small flex-grow-1" style="white-space: pre-line;" id="prompt-text-3" data-i18n="todolist.assessment.prompts.item3" data-i18n-params='<?= $prompt_params_json; ?>'>基于我在<?= htmlspecialchars($start); ?> 到 <?= htmlspecialchars($end); ?>期间的待办记录，请总结三大分类下最有代表性的行动，要善于识别措辞不同但本质相同的事项并合并，最终按条目输出每类的重点事项清单。</div>
              <button type="button" class="btn btn-outline-primary btn-sm copy-prompt align-self-lg-start" data-target="prompt-text-3" data-i18n="todolist.assessment.prompts.copy">复制提示词</button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="todolist.assessment.prompts.close">关闭</button>
      </div>
    </div>
  </div>
</div>
<?php include 'footer.php'; ?>
