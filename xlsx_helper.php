<?php
if (!function_exists('xlsx_write_workbook')) {
    function xlsx_write_workbook(array $sheets): string
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive extension required for XLSX export');
        }
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmpFile === false) {
            throw new RuntimeException('Unable to create temporary file for XLSX export');
        }
        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to open temporary XLSX file');
        }
        $normalizedSheets = [];
        $sharedStrings = [];
        $sharedMap = [];
        foreach ($sheets as $sheetIndex => $sheet) {
            $name = $sheet['name'] ?? ('Sheet' . ($sheetIndex + 1));
            $rows = $sheet['rows'] ?? [];
            $normalizedRows = [];
            foreach ($rows as $row) {
                $row = array_map(function ($cell) use (&$sharedStrings, &$sharedMap) {
                    if ($cell === null) {
                        $cell = '';
                    }
                    $cell = (string)$cell;
                    if (!array_key_exists($cell, $sharedMap)) {
                        $sharedMap[$cell] = count($sharedStrings);
                        $sharedStrings[] = $cell;
                    }
                    return $sharedMap[$cell];
                }, $row);
                $normalizedRows[] = $row;
            }
            $normalizedSheets[] = [
                'name' => $name,
                'rows' => $normalizedRows,
            ];
        }
        $zip->addFromString('[Content_Types].xml', xlsx_build_content_types(count($normalizedSheets)));
        $zip->addFromString('_rels/.rels', xlsx_build_root_rels());
        $zip->addFromString('docProps/app.xml', xlsx_build_app_xml(array_column($normalizedSheets, 'name')));
        $zip->addFromString('docProps/core.xml', xlsx_build_core_xml());
        $zip->addFromString('xl/workbook.xml', xlsx_build_workbook_xml($normalizedSheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', xlsx_build_workbook_rels(count($normalizedSheets)));
        $zip->addFromString('xl/sharedStrings.xml', xlsx_build_shared_strings_xml($sharedStrings));
        $zip->addFromString('xl/styles.xml', xlsx_build_styles_xml());
        foreach ($normalizedSheets as $index => $sheet) {
            $sheetXml = xlsx_build_sheet_xml($sheet['rows']);
            $zip->addFromString('xl/worksheets/sheet' . ($index + 1) . '.xml', $sheetXml);
        }
        $zip->close();
        return $tmpFile;
    }
    function xlsx_build_content_types(int $sheetCount): string
    {
        $sheetOverrides = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $sheetOverrides .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . $sheetOverrides
            . '</Types>';
    }
    function xlsx_build_root_rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }
    function xlsx_build_app_xml(array $sheetNames): string
    {
        $worksheetsCount = count($sheetNames);
        $titles = '';
        foreach ($sheetNames as $index => $name) {
            $titles .= '<vt:lpstr>' . htmlspecialchars($name, ENT_QUOTES | ENT_XML1) . '</vt:lpstr>';
        }
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>PHP XLSX Builder</Application>'
            . '<DocSecurity>0</DocSecurity>'
            . '<ScaleCrop>false</ScaleCrop>'
            . '<HeadingPairs><vt:vector size="2" baseType="variant">'
            . '<vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant>'
            . '<vt:variant><vt:i4>' . $worksheetsCount . '</vt:i4></vt:variant>'
            . '</vt:vector></HeadingPairs>'
            . '<TitlesOfParts><vt:vector size="' . max(1, $worksheetsCount) . '" baseType="lpstr">'
            . ($titles !== '' ? $titles : '<vt:lpstr>Sheet1</vt:lpstr>')
            . '</vt:vector></TitlesOfParts>'
            . '</Properties>';
    }
    function xlsx_build_core_xml(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
            . '<dc:creator>YourTeam</dc:creator>'
            . '<cp:revision>1</cp:revision>'
            . '</cp:coreProperties>';
    }
    function xlsx_build_workbook_xml(array $sheets): string
    {
        $sheetXml = '';
        foreach ($sheets as $index => $sheet) {
            $name = htmlspecialchars($sheet['name'], ENT_QUOTES | ENT_XML1);
            $sheetXml .= '<sheet name="' . $name . '" sheetId="' . ($index + 1) . '" r:id="rId' . ($index + 1) . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . ($sheetXml ?: '<sheet name="Sheet1" sheetId="1" r:id="rId1"/>') . '</sheets>'
            . '</workbook>';
    }
    function xlsx_build_workbook_rels(int $sheetCount): string
    {
        $rels = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . ($rels ?: '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>')
            . '</Relationships>';
    }
    function xlsx_build_shared_strings_xml(array $strings): string
    {
        $count = count($strings);
        $xmlStrings = '';
        foreach ($strings as $text) {
            $xmlStrings .= '<si><t xml:space="preserve">' . htmlspecialchars($text, ENT_QUOTES | ENT_XML1) . '</t></si>';
        }
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $count . '" uniqueCount="' . $count . '">' . $xmlStrings . '</sst>';
    }
    function xlsx_build_styles_xml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><name val="等线"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
    function xlsx_build_sheet_xml(array $rows): string
    {
        $sheetData = '';
        foreach ($rows as $rowIndex => $row) {
            $cellXml = '';
            foreach ($row as $colIndex => $sharedIndex) {
                $cellRef = xlsx_column_letter($colIndex + 1) . ($rowIndex + 1);
                $cellXml .= '<c r="' . $cellRef . '" t="s"><v>' . $sharedIndex . '</v></c>';
            }
            $sheetData .= '<row r="' . ($rowIndex + 1) . '">' . $cellXml . '</row>';
        }
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetData>' . $sheetData . '</sheetData>'
            . '</worksheet>';
    }
    function xlsx_column_letter(int $columnNumber): string
    {
        $letter = '';
        while ($columnNumber > 0) {
            $mod = ($columnNumber - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $columnNumber = (int)(($columnNumber - $mod) / 26);
        }
        return $letter;
    }
}
if (!function_exists('xlsx_parse_rows')) {
    function xlsx_parse_rows(string $filePath, int $sheetIndex = 1): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive extension required for XLSX import');
        }
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('Unable to open XLSX file');
        }
        $sharedStrings = [];
        $sharedIndex = $zip->locateName('xl/sharedStrings.xml');
        if ($sharedIndex !== false) {
            $sharedXml = $zip->getFromIndex($sharedIndex);
            if ($sharedXml !== false) {
                $xml = @simplexml_load_string($sharedXml);
                if ($xml) {
                    foreach ($xml->si as $si) {
                        $sharedStrings[] = xlsx_extract_shared_string($si);
                    }
                }
            }
        }
        $sheetPath = 'xl/worksheets/sheet' . $sheetIndex . '.xml';
        $sheetIndexInZip = $zip->locateName($sheetPath);
        if ($sheetIndexInZip === false) {
            $zip->close();
            return [];
        }
        $sheetXmlContent = $zip->getFromIndex($sheetIndexInZip);
        $zip->close();
        if ($sheetXmlContent === false) {
            return [];
        }
        $sheetXml = @simplexml_load_string($sheetXmlContent);
        if (!$sheetXml) {
            return [];
        }
        $rows = [];
        foreach ($sheetXml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $cellType = (string)$cell['t'];
                $cellRef = (string)$cell['r'];
                $colRef = preg_replace('/\d+/', '', $cellRef);
                $colIndex = xlsx_column_index($colRef);
                $value = '';
                if ($cellType === 's') {
                    $index = (int)$cell->v;
                    $value = $sharedStrings[$index] ?? '';
                } elseif ($cellType === 'inlineStr') {
                    $value = xlsx_extract_inline_string($cell);
                } else {
                    $value = isset($cell->v) ? (string)$cell->v : '';
                }
                $cells[$colIndex] = $value;
            }
            if (!empty($cells)) {
                ksort($cells);
                $rowValues = [];
                $maxIndex = max(array_keys($cells));
                for ($i = 0; $i <= $maxIndex; $i++) {
                    $rowValues[] = $cells[$i] ?? '';
                }
                $rows[] = $rowValues;
            } else {
                $rows[] = [];
            }
        }
        return $rows;
    }
    function xlsx_extract_shared_string(SimpleXMLElement $si): string
    {
        $text = '';
        if (isset($si->t)) {
            $text = (string)$si->t;
        } elseif (!empty($si->r)) {
            foreach ($si->r as $r) {
                $text .= (string)$r->t;
            }
        }
        return $text;
    }
    function xlsx_extract_inline_string(SimpleXMLElement $cell): string
    {
        if (isset($cell->is) && isset($cell->is->t)) {
            return (string)$cell->is->t;
        }
        return isset($cell->v) ? (string)$cell->v : '';
    }
    function xlsx_column_index(string $letters): int
    {
        $letters = strtoupper($letters);
        $length = strlen($letters);
        $index = 0;
        for ($i = 0; $i < $length; $i++) {
            $index *= 26;
            $index += ord($letters[$i]) - 65 + 1;
        }
        return $index - 1;
    }
}
?>
