<?php
require 'auth.php';
require_once 'member_extra_helpers.php';

// Helper: convert value to UTF-8 from common Chinese encodings if needed
function to_utf8($s) {
    if ($s === null) return null;
    if (!is_string($s)) return $s;
    if (mb_check_encoding($s, 'UTF-8')) return $s;
    foreach (['GB18030', 'GBK', 'CP936'] as $enc) {
        $t = @mb_convert_encoding($s, 'UTF-8', $enc);
        if ($t !== false && $t !== '') return $t;
    }
    $t = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
    return $t !== false ? $t : $s;
}

// Normalize header key
function norm($s) {
    return strtolower(trim(preg_replace('/\s+/', '', $s ?? '')));
}

function starts_with($haystack, $needle)
{
    $haystack = (string)$haystack;
    $needle = (string)$needle;
    if ($needle === '') {
        return true;
    }
    return strncmp($haystack, $needle, strlen($needle)) === 0;
}

function getBaseColumns(): array
{
    return [
        'campus_id' => [
            'db' => 'campus_id',
            'required' => true,
            'aliases' => ['campusid', 'campus_id', 'campusid号', 'campus id', '一卡通号', '学号', '校园卡号', '一卡通ID'],
            'label_en' => 'Campus ID',
            'label_zh' => '一卡通号',
        ],
        'name' => [
            'db' => 'name',
            'required' => true,
            'aliases' => ['name', '姓名', '成员姓名'],
            'label_en' => 'Name',
            'label_zh' => '姓名',
        ],
        'email' => [
            'db' => 'email',
            'required' => false,
            'aliases' => ['email', '邮箱', '电子邮箱', '邮件'],
            'label_en' => 'Email',
            'label_zh' => '正式邮箱',
        ],
        'identity_number' => [
            'db' => 'identity_number',
            'required' => false,
            'aliases' => ['identitynumber', 'identity_number', '身份证', '身份证号', 'idnumber'],
            'label_en' => 'Identity Number',
            'label_zh' => '身份证号',
        ],
        'year_of_join' => [
            'db' => 'year_of_join',
            'required' => false,
            'aliases' => ['yearofjoin', 'year_of_join', 'yearjoin', '入学年份', '入学年份(年)', '入学年'],
            'label_en' => 'Year of Join',
            'label_zh' => '入学年份',
        ],
        'current_degree' => [
            'db' => 'current_degree',
            'required' => false,
            'aliases' => ['currentdegree', 'current_degree', '已获学位', '当前学位'],
            'label_en' => 'Degree Achieved',
            'label_zh' => '已获学位',
        ],
        'degree_pursuing' => [
            'db' => 'degree_pursuing',
            'required' => false,
            'aliases' => ['degreepursuing', 'degree_pursuing', '当前学历', '在读学历'],
            'label_en' => 'Degree Pursuing',
            'label_zh' => '当前学历',
        ],
        'phone' => [
            'db' => 'phone',
            'required' => false,
            'aliases' => ['phone', 'mobile', '手机号', '联系电话', '电话'],
            'label_en' => 'Phone',
            'label_zh' => '手机号',
        ],
        'wechat' => [
            'db' => 'wechat',
            'required' => false,
            'aliases' => ['wechat', '微信号', '微信'],
            'label_en' => 'WeChat',
            'label_zh' => '微信号',
        ],
        'department' => [
            'db' => 'department',
            'required' => false,
            'aliases' => ['department', '学院', '所在学院', '单位', '所处学院/单位'],
            'label_en' => 'Department',
            'label_zh' => '所处学院/单位',
        ],
        'workplace' => [
            'db' => 'workplace',
            'required' => false,
            'aliases' => ['workplace', '工作地点', '办公地点'],
            'label_en' => 'Workplace',
            'label_zh' => '工作地点',
        ],
        'homeplace' => [
            'db' => 'homeplace',
            'required' => false,
            'aliases' => ['homeplace', '家庭住址', '家庭地址', '家庭所在地'],
            'label_en' => 'Homeplace',
            'label_zh' => '家庭住址',
        ],
        'status' => [
            'db' => 'status',
            'required' => false,
            'aliases' => ['status', '成员状态', '状态', '人员状态'],
            'label_en' => 'Status',
            'label_zh' => '成员状态',
        ],
    ];
}

function normalizeStatus($value)
{
    $value = strtolower(trim((string)$value));
    if ($value === '' || $value === 'in_work') {
        return 'in_work';
    }
    $map = [
        'inwork' => 'in_work',
        '在岗' => 'in_work',
        '在职' => 'in_work',
        '在组' => 'in_work',
        '在读' => 'in_work',
        'active' => 'in_work',
        '在籍' => 'in_work',
        'exited' => 'exited',
        '离岗' => 'exited',
        '离职' => 'exited',
        '退出' => 'exited',
        '离开' => 'exited',
        'inactive' => 'exited',
    ];
    return $map[$value] ?? null;
}

function sanitizeYear($value)
{
    $value = trim((string)$value);
    if ($value === '') return null;
    if (!preg_match('/^-?\\d+$/', $value)) {
        return null;
    }
    return (int)$value;
}

function outputTemplateCSV(array $baseColumns, array $extraAttributes): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="member_import_template.csv"');
    $fp = fopen('php://output', 'w');
    if ($fp === false) {
        exit;
    }
    fwrite($fp, "\xEF\xBB\xBF");
    $headers = [];
    foreach ($baseColumns as $column) {
        $headers[] = ($column['label_zh'] ?? '') . ' / ' . ($column['label_en'] ?? '');
    }
    foreach ($extraAttributes as $attr) {
        $zh = trim((string)($attr['name_zh'] ?? ''));
        $en = trim((string)($attr['name_en'] ?? ''));
        $headers[] = ($zh !== '' ? $zh : ($en !== '' ? $en : ('Extra ' . $attr['id']))) . ' / ' . ($en !== '' ? $en : ($zh !== '' ? $zh : ('Extra ' . $attr['id'])));
    }
    fputcsv($fp, $headers);
    fclose($fp);
    exit;
}

$baseColumns = getBaseColumns();
$extraAttributes = getMemberExtraAttributes($pdo);

$aliasToColumn = [];
foreach ($baseColumns as $key => $column) {
    $aliasToColumn[norm($column['label_en'])] = $key;
    $aliasToColumn[norm($column['label_zh'])] = $key;
    $aliasToColumn[$key] = $key;
    foreach ($column['aliases'] as $alias) {
        $aliasToColumn[norm($alias)] = $key;
    }
}

$extraAliasToId = [];
foreach ($extraAttributes as $attr) {
    $attrId = (int)($attr['id'] ?? 0);
    if ($attrId <= 0) continue;
    $extraAliasToId['extra:' . $attrId] = $attrId;
    $extraAliasToId[norm('extra' . $attrId)] = $attrId;
    $extraAliasToId[norm('attribute' . $attrId)] = $attrId;
    $nameEn = norm($attr['name_en'] ?? '');
    $nameZh = norm($attr['name_zh'] ?? '');
    if ($nameEn !== '') $extraAliasToId[$nameEn] = $attrId;
    if ($nameZh !== '') $extraAliasToId[$nameZh] = $attrId;
}

if (isset($_GET['download']) && $_GET['download'] === 'template') {
    outputTemplateCSV($baseColumns, $extraAttributes);
}

$status = null;
$preview = null;
$payloadEncoded = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_import'])) {
        $payloadEncoded = $_POST['payload'] ?? '';
        $payloadJson = base64_decode($payloadEncoded, true);
        $payload = $payloadJson ? json_decode($payloadJson, true) : null;
        if (!is_array($payload) || !isset($payload['rows']) || !is_array($payload['rows'])) {
            $status = ['type' => 'danger', 'msg' => '无法识别导入数据，请重新上传文件。Unable to read import payload.'];
        } else {
            $hasUpdates = !empty($payload['has_updates']);
            $ackUpdates = isset($_POST['acknowledge_updates']) && $_POST['acknowledge_updates'] === '1';
            if ($hasUpdates && !$ackUpdates) {
                $status = ['type' => 'warning', 'msg' => '存在将覆盖的成员记录，请勾选确认后再导入。Please acknowledge updates before importing.'];
            } else {
                $rows = $payload['rows'];
                $campusIds = [];
                foreach ($rows as $row) {
                    if (!empty($row['fields']['campus_id'])) {
                        $campusIds[] = $row['fields']['campus_id'];
                    }
                }
                $existingMap = [];
                if (!empty($campusIds)) {
                    $uniqueIds = array_values(array_unique($campusIds));
                    $placeholders = implode(',', array_fill(0, count($uniqueIds), '?'));
                    $stmt = $pdo->prepare("SELECT id, campus_id FROM members WHERE campus_id IN ($placeholders)");
                    $stmt->execute($uniqueIds);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $existingMap[$row['campus_id']] = (int)$row['id'];
                    }
                }

                $insertCount = 0;
                $updateCount = 0;
                $skipCount = 0;

                try {
                    $pdo->beginTransaction();

                    $nextOrderStmt = $pdo->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM members');
                    $nextSortOrder = (int)($nextOrderStmt->fetchColumn() ?? 0);

                    $insertStmt = $pdo->prepare('INSERT INTO members (campus_id, name, email, identity_number, year_of_join, current_degree, degree_pursuing, phone, wechat, department, workplace, homeplace, status, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                    $updateStmt = $pdo->prepare('UPDATE members SET name=?, email=?, identity_number=?, year_of_join=?, current_degree=?, degree_pursuing=?, phone=?, wechat=?, department=?, workplace=?, homeplace=?, status=? WHERE id=?');
                    $extraUpsertStmt = $pdo->prepare('INSERT INTO member_extra_values (member_id, attribute_id, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');

                    foreach ($rows as $row) {
                        $fields = $row['fields'] ?? [];
                        $extraValues = $row['extra'] ?? [];
                        $issues = $row['issues'] ?? [];
                        $action = $row['action'] ?? 'skip';

                        $campusId = trim((string)($fields['campus_id'] ?? ''));
                        if ($campusId === '' || !empty($issues) || !in_array($action, ['create', 'update'], true)) {
                            $skipCount++;
                            continue;
                        }

                        $name = trim((string)($fields['name'] ?? ''));
                        if ($name === '') {
                            $skipCount++;
                            continue;
                        }

                        $email = trim((string)($fields['email'] ?? ''));
                        $identityNumber = trim((string)($fields['identity_number'] ?? ''));
                        $yearOfJoin = sanitizeYear($fields['year_of_join'] ?? null);
                        $currentDegree = trim((string)($fields['current_degree'] ?? ''));
                        $degreePursuing = trim((string)($fields['degree_pursuing'] ?? ''));
                        $phone = trim((string)($fields['phone'] ?? ''));
                        $wechat = trim((string)($fields['wechat'] ?? ''));
                        $department = trim((string)($fields['department'] ?? ''));
                        $workplace = trim((string)($fields['workplace'] ?? ''));
                        $homeplace = trim((string)($fields['homeplace'] ?? ''));
                        $statusValue = normalizeStatus($fields['status'] ?? '');
                        if ($statusValue === null) {
                            $statusValue = 'in_work';
                        }

                        $existingId = $existingMap[$campusId] ?? null;
                        if ($action === 'update' && $existingId) {
                            $updateStmt->execute([
                                $name,
                                $email === '' ? null : $email,
                                $identityNumber === '' ? null : $identityNumber,
                                $yearOfJoin,
                                $currentDegree === '' ? null : $currentDegree,
                                $degreePursuing === '' ? null : $degreePursuing,
                                $phone === '' ? null : $phone,
                                $wechat === '' ? null : $wechat,
                                $department === '' ? null : $department,
                                $workplace === '' ? null : $workplace,
                                $homeplace === '' ? null : $homeplace,
                                $statusValue,
                                $existingId,
                            ]);
                            $memberId = $existingId;
                            $updateCount++;
                        } elseif ($action === 'create' && !$existingId) {
                            $insertStmt->execute([
                                $campusId,
                                $name,
                                $email === '' ? null : $email,
                                $identityNumber === '' ? null : $identityNumber,
                                $yearOfJoin,
                                $currentDegree === '' ? null : $currentDegree,
                                $degreePursuing === '' ? null : $degreePursuing,
                                $phone === '' ? null : $phone,
                                $wechat === '' ? null : $wechat,
                                $department === '' ? null : $department,
                                $workplace === '' ? null : $workplace,
                                $homeplace === '' ? null : $homeplace,
                                $statusValue,
                                $nextSortOrder++,
                            ]);
                            $memberId = (int)$pdo->lastInsertId();
                            $insertCount++;
                        } else {
                            $skipCount++;
                            continue;
                        }

                        if (!empty($extraValues) || $action === 'create') {
                            if ($action === 'create') {
                                foreach ($extraAttributes as $attr) {
                                    $attrId = (int)$attr['id'];
                                    if ($attrId <= 0) continue;
                                    $value = array_key_exists($attrId, $extraValues)
                                        ? (string)$extraValues[$attrId]
                                        : (string)($attr['default_value'] ?? '');
                                    $extraUpsertStmt->execute([$memberId, $attrId, $value]);
                                }
                            } else {
                                foreach ($extraValues as $attrId => $value) {
                                    $attrId = (int)$attrId;
                                    if ($attrId <= 0) continue;
                                    $extraUpsertStmt->execute([$memberId, $attrId, (string)$value]);
                                }
                            }
                        }
                    }

                    $pdo->commit();
                    $status = ['type' => 'success', 'msg' => "导入完成：新增 {$insertCount} 条，更新 {$updateCount} 条，跳过 {$skipCount} 条。Import finished. Inserted {$insertCount}, updated {$updateCount}, skipped {$skipCount}."];
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $status = ['type' => 'danger', 'msg' => '导入失败，请稍后重试。Import failed, please try again.'];
                }
            }
        }
    } else {
        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            $status = ['type' => 'danger', 'msg' => '请选择需要导入的文件。Please choose a CSV file to upload.'];
        } else {
            $fp = fopen($_FILES['file']['tmp_name'], 'r');
            if ($fp === false) {
                $status = ['type' => 'danger', 'msg' => '无法读取上传的文件。Cannot open the uploaded file.'];
            } else {
                $rawHeader = fgetcsv($fp);
                if ($rawHeader === false) {
                    fclose($fp);
                    $status = ['type' => 'danger', 'msg' => '文件为空，请检查后重新上传。Uploaded file appears to be empty.'];
                } else {
                    $header = array_map(fn($v) => to_utf8($v), $rawHeader);
                    $headerMap = [];
                    foreach ($header as $idx => $cell) {
                        $key = norm($cell);
                        if ($key === '') continue;
                        if (isset($aliasToColumn[$key])) {
                            $columnKey = $aliasToColumn[$key];
                            if (!isset($headerMap[$columnKey])) {
                                $headerMap[$columnKey] = $idx;
                            }
                        } elseif (isset($extraAliasToId[$key])) {
                            $attrId = $extraAliasToId[$key];
                            $headerMap['extra:' . $attrId] = $idx;
                        }
                    }

                    $missing = [];
                    foreach ($baseColumns as $key => $column) {
                        if (!empty($column['required']) && !isset($headerMap[$key])) {
                            $missing[] = ($column['label_zh'] ?? $key) . ' / ' . ($column['label_en'] ?? $key);
                        }
                    }

                    if (!empty($missing)) {
                        fclose($fp);
                        $status = ['type' => 'danger', 'msg' => '缺少必要字段：' . implode('，', $missing) . '。Missing required columns.'];
                    } else {
                        $rows = [];
                        $rowNumber = 1;
                        $campusIds = [];
                        $extraColumns = [];
                        foreach ($headerMap as $key => $idx) {
                            if (starts_with($key, 'extra:')) {
                                $attrId = (int)substr($key, 6);
                                if ($attrId > 0) {
                                    $extraColumns[$attrId] = true;
                                }
                            }
                        }

                        while (($row = fgetcsv($fp)) !== false) {
                            $rowNumber++;
                            if ($row === [null] || count($row) === 0) {
                                continue;
                            }
                            foreach ($row as $i => $value) {
                                $row[$i] = to_utf8(is_string($value) ? trim($value) : $value);
                            }

                            $fields = [];
                            foreach ($baseColumns as $key => $column) {
                                $idx = $headerMap[$key] ?? null;
                                $fields[$key] = $idx !== null && array_key_exists($idx, $row) ? (string)$row[$idx] : '';
                            }

                            $extraValues = [];
                            foreach ($extraColumns as $attrId => $_) {
                                $mapKey = 'extra:' . $attrId;
                                $idx = $headerMap[$mapKey] ?? null;
                                if ($idx !== null && array_key_exists($idx, $row)) {
                                    $extraValues[$attrId] = (string)$row[$idx];
                                }
                            }

                            $issues = [];
                            $campusId = trim((string)($fields['campus_id'] ?? ''));
                            if ($campusId === '') {
                                $issues[] = '缺少一卡通号 / Missing campus ID';
                            } else {
                                $campusIds[] = $campusId;
                            }
                            $name = trim((string)($fields['name'] ?? ''));
                            if ($name === '') {
                                $issues[] = '缺少姓名 / Missing name';
                            }
                            $statusRaw = $fields['status'] ?? '';
                            if ($statusRaw !== '') {
                                $normalizedStatus = normalizeStatus($statusRaw);
                                if ($normalizedStatus === null) {
                                    $issues[] = '无法识别的成员状态 / Unknown status';
                                } else {
                                    $fields['status'] = $normalizedStatus;
                                }
                            }
                            $year = $fields['year_of_join'] ?? '';
                            if ($year !== '') {
                                $yearValue = sanitizeYear($year);
                                if ($yearValue === null) {
                                    $issues[] = '入学年份需为数字 / Year of join must be numeric';
                                } else {
                                    $fields['year_of_join'] = $yearValue;
                                }
                            }

                            $rows[] = [
                                'row_number' => $rowNumber,
                                'fields' => $fields,
                                'extra' => $extraValues,
                                'issues' => $issues,
                                'action' => 'create',
                                'existing_member_id' => null,
                            ];
                        }
                        fclose($fp);

                        $duplicateIds = [];
                        $counts = array_count_values($campusIds);
                        foreach ($counts as $id => $cnt) {
                            if ($cnt > 1) {
                                $duplicateIds[$id] = true;
                            }
                        }

                        $existingMap = [];
                        if (!empty($campusIds)) {
                            $uniqueIds = array_values(array_unique($campusIds));
                            $placeholders = implode(',', array_fill(0, count($uniqueIds), '?'));
                            $stmt = $pdo->prepare("SELECT id, campus_id FROM members WHERE campus_id IN ($placeholders)");
                            $stmt->execute($uniqueIds);
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $existingMap[$row['campus_id']] = (int)$row['id'];
                            }
                        }

                        $stats = ['create' => 0, 'update' => 0, 'skip' => 0];
                        foreach ($rows as &$row) {
                            $campusId = trim((string)($row['fields']['campus_id'] ?? ''));
                            if ($campusId !== '' && isset($duplicateIds[$campusId])) {
                                $row['issues'][] = '表格中存在重复的一卡通号 / Duplicate campus ID in file';
                            }
                            if (!empty($row['issues'])) {
                                $row['action'] = 'skip';
                                $stats['skip']++;
                                continue;
                            }
                            if ($campusId !== '' && isset($existingMap[$campusId])) {
                                $row['action'] = 'update';
                                $row['existing_member_id'] = $existingMap[$campusId];
                                $stats['update']++;
                            } else {
                                $row['action'] = 'create';
                                $stats['create']++;
                            }
                        }
                        unset($row);

                        $preview = [
                            'rows' => $rows,
                            'stats' => $stats,
                            'extra_columns' => array_keys($extraColumns),
                            'has_updates' => $stats['update'] > 0,
                        ];

                        $payload = [
                            'rows' => [],
                            'has_updates' => $preview['has_updates'],
                        ];
                        foreach ($rows as $row) {
                            $payload['rows'][] = [
                                'row_number' => $row['row_number'],
                                'fields' => $row['fields'],
                                'extra' => $row['extra'],
                                'issues' => $row['issues'],
                                'action' => $row['action'],
                            ];
                        }
                        $payloadEncoded = base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));
                    }
                }
            }
        }
    }
}

include 'header.php';
?>
<h2 data-i18n="members_import.title">Import Members from CSV</h2>
<div class="mb-3 d-flex gap-2 flex-wrap">
  <a class="btn btn-outline-secondary" href="members_import.php?download=template" data-i18n="members_import.download_template">Download template</a>
  <a class="btn btn-secondary" href="members.php" data-i18n="members_import.back">Back to member list</a>
</div>
<?php if ($status): ?>
  <div class="alert alert-<?php echo htmlspecialchars($status['type']); ?>" role="alert">
    <?php echo htmlspecialchars($status['msg']); ?>
  </div>
<?php endif; ?>

<?php if ($preview): ?>
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title" data-i18n="members_import.preview.title">Preview import result</h5>
      <p class="card-text">
        <span data-i18n="members_import.preview.summary.new">New records</span>:
        <strong><?php echo (int)$preview['stats']['create']; ?></strong>
        &nbsp;|&nbsp;
        <span data-i18n="members_import.preview.summary.update">Will update</span>:
        <strong><?php echo (int)$preview['stats']['update']; ?></strong>
        &nbsp;|&nbsp;
        <span data-i18n="members_import.preview.summary.skip">Skipped</span>:
        <strong><?php echo (int)$preview['stats']['skip']; ?></strong>
      </p>
      <?php if ($preview['has_updates']): ?>
        <div class="alert alert-warning" data-i18n="members_import.preview.update_warning">Existing members with the same Campus ID will be updated. Please review carefully.</div>
      <?php endif; ?>
      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th data-i18n="members_import.preview.column.campus_id">Campus ID</th>
              <th data-i18n="members_import.preview.column.name">Name</th>
              <th data-i18n="members_import.preview.column.status">Result</th>
<?php foreach ($baseColumns as $key => $column): if (in_array($key, ['campus_id','name'])) continue; ?>
              <th><?php echo htmlspecialchars(($column['label_zh'] ?? '') . ' / ' . ($column['label_en'] ?? $key)); ?></th>
<?php endforeach; ?>
<?php if (!empty($preview['extra_columns'])): ?>
<?php foreach ($preview['extra_columns'] as $attrId):
    $attr = null;
    foreach ($extraAttributes as $candidate) {
        if ((int)$candidate['id'] === (int)$attrId) { $attr = $candidate; break; }
    }
    if ($attr):
        $label = trim(($attr['name_zh'] ?? '') . ' / ' . ($attr['name_en'] ?? ''));
        if ($label === ' / ') {
            $label = 'Extra #' . $attrId;
        }
?>
              <th><?php echo htmlspecialchars($label); ?></th>
<?php endif; endforeach; ?>
<?php endif; ?>
              <th data-i18n="members_import.preview.column.issues">Issues</th>
            </tr>
          </thead>
          <tbody>
<?php foreach ($preview['rows'] as $row): ?>
            <tr>
              <td><?php echo (int)$row['row_number']; ?></td>
              <td><?php echo htmlspecialchars($row['fields']['campus_id'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($row['fields']['name'] ?? ''); ?></td>
              <td>
<?php
    $actionKey = 'members_import.preview.action.skip';
    $badgeClass = 'bg-secondary';
    if ($row['action'] === 'create') {
        $actionKey = 'members_import.preview.action.create';
        $badgeClass = 'bg-success';
    } elseif ($row['action'] === 'update') {
        $actionKey = 'members_import.preview.action.update';
        $badgeClass = 'bg-warning text-dark';
    }
?>
                <span class="badge <?php echo $badgeClass; ?>" data-i18n="<?php echo $actionKey; ?>">
                  <?php if ($row['action'] === 'create') { echo 'New'; } elseif ($row['action'] === 'update') { echo 'Update'; } else { echo 'Skip'; } ?>
                </span>
              </td>
<?php foreach ($baseColumns as $key => $column): if (in_array($key, ['campus_id','name'])) continue; ?>
              <td><?php echo htmlspecialchars($row['fields'][$key] ?? ''); ?></td>
<?php endforeach; ?>
<?php if (!empty($preview['extra_columns'])): ?>
<?php foreach ($preview['extra_columns'] as $attrId): ?>
              <td><?php echo htmlspecialchars($row['extra'][$attrId] ?? ''); ?></td>
<?php endforeach; ?>
<?php endif; ?>
              <td>
<?php if (!empty($row['issues'])): ?>
                <ul class="mb-0 ps-3">
<?php foreach ($row['issues'] as $issue): ?>
                  <li><?php echo htmlspecialchars($issue); ?></li>
<?php endforeach; ?>
                </ul>
<?php endif; ?>
              </td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <form method="post" class="mt-3">
        <input type="hidden" name="payload" value="<?php echo htmlspecialchars($payloadEncoded); ?>">
        <input type="hidden" name="confirm_import" value="1">
<?php if ($preview['has_updates']): ?>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" value="1" id="acknowledge_updates" name="acknowledge_updates" required>
          <label class="form-check-label" for="acknowledge_updates" data-i18n="members_import.preview.ack_updates">I understand that existing records will be updated.</label>
        </div>
<?php endif; ?>
        <button type="submit" class="btn btn-primary" data-i18n="members_import.preview.confirm">Confirm import</button>
        <a href="members_import.php" class="btn btn-secondary" data-i18n="members_import.preview.restart">Upload another file</a>
      </form>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title" data-i18n="members_import.upload.title">Upload CSV file</h5>
      <p class="card-text" data-i18n="members_import.upload.hint">Please use the template to prepare data. Only CSV format is supported.</p>
      <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="file" class="form-label" data-i18n="members_import.upload.label">Select CSV file</label>
          <input type="file" name="file" id="file" accept=".csv" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" data-i18n="members_import.upload.preview">Preview import</button>
        <a href="members.php" class="btn btn-secondary" data-i18n="members_import.cancel">Cancel</a>
      </form>
    </div>
  </div>
<?php endif; ?>
<?php include 'footer.php'; ?>
