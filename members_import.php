<?php
require 'auth.php'; // must create $pdo (PDO) and connect to DB

// --- Debug during development (comment out in production) ---
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
//$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Helper: convert value to UTF-8 from common Chinese encodings if needed
function to_utf8($s) {
    if ($s === null) return null;
    if (!is_string($s)) return $s;
    if (mb_check_encoding($s, 'UTF-8')) return $s;
    // Try GB18030 / GBK / CP936 fallback chain
    foreach (['GB18030', 'GBK', 'CP936'] as $enc) {
        $t = @mb_convert_encoding($s, 'UTF-8', $enc);
        if ($t !== false && $t !== '') return $t;
    }
    // Last resort: force to UTF-8 ignoring invalid bytes
    $t = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
    return $t !== false ? $t : $s;
}

// Normalize header key
function norm($s) {
    return strtolower(trim(preg_replace('/\s+/', '', $s)));
}

$status = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
        $status = ['type' => 'danger', 'msg' => 'No file uploaded.'];
    } else {
        $fp = fopen($_FILES['file']['tmp_name'], 'r');
        if ($fp === false) {
            $status = ['type' => 'danger', 'msg' => 'Cannot open uploaded file.'];
        } else {
            // Read header
            $rawHeader = fgetcsv($fp);
            if ($rawHeader === false) {
                fclose($fp);
                $status = ['type' => 'danger', 'msg' => 'CSV appears empty.'];
            } else {
                // Convert header cells to UTF-8 and normalize
                $header = array_map(fn($v) => to_utf8($v), $rawHeader);
                $hmap = [];
                foreach ($header as $i => $h) {
                    $hmap[norm($h)] = $i;
                }

                // Expected header names -> DB columns
                $expected = [
                    'campusid'        => 'campus_id',
                    'name'            => 'name',
                    'email'           => 'email',
                    'identitynumber'  => 'identity_number',
                    'yearofjoin'      => 'year_of_join',
                    'currentdegree'   => 'current_degree',
                    'degreepursuing'  => 'degree_pursuing',
                    'phone'           => 'phone',
                    'wechat'          => 'wechat',
                    'department'      => 'department',
                    'workplace'       => 'workplace',
                    'homeplace'       => 'homeplace',
                    'status'         => 'status',
                ];

                // Verify required headers exist
                $missing = [];
                foreach ($expected as $hdr => $col) {
                    if ($hdr === 'status') continue; // status column optional
                    if (!array_key_exists($hdr, $hmap)) {
                        $missing[] = $hdr;
                    }
                }
                if (!empty($missing)) {
                    fclose($fp);
                    $status = [
                        'type' => 'danger',
                        'msg'  => 'CSV header missing columns: ' . implode(', ', $missing)
                    ];
                } else {
                    // Prepare SQL
                    $sql = 'INSERT INTO members
                        (campus_id, name, email, identity_number, year_of_join, current_degree, degree_pursuing, phone, wechat, department, workplace, homeplace, status)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
                        ON DUPLICATE KEY UPDATE
                            name=VALUES(name),
                            email=VALUES(email),
                            identity_number=VALUES(identity_number),
                            year_of_join=VALUES(year_of_join),
                            current_degree=VALUES(current_degree),
                            degree_pursuing=VALUES(degree_pursuing),
                            phone=VALUES(phone),
                            wechat=VALUES(wechat),
                            department=VALUES(department),
                            workplace=VALUES(workplace),
                            homeplace=VALUES(homeplace),
                            status=VALUES(status)';

                    $stmt = $pdo->prepare($sql);

                    $pdo->beginTransaction();
                    $imported = 0;
                    $updated  = 0;
                    $skipped  = 0;
                    $rownum   = 1; // data rows start at 1 after header

                    while (($row = fgetcsv($fp)) !== false) {
                        $rownum++;
                        if ($row === [null] || count($row) === 0) { $skipped++; continue; }

                        // Convert all cells to UTF-8 and trim
                        foreach ($row as $i => $v) {
                            $row[$i] = to_utf8(is_string($v) ? trim($v) : $v);
                        }

                        // Build record in DB order
                        $get = function($key) use ($row, $hmap) {
                            if (!array_key_exists($key, $hmap)) return null;
                            $idx = $hmap[$key];
                            return array_key_exists($idx, $row) ? $row[$idx] : null;
                        };

                        $campus_id       = (string)$get('campusid');
                        $name            = $get('name');
                        $email           = $get('email');
                        $identity_number = $get('identitynumber');
                        $year_of_join    = $get('yearofjoin');
                        $current_degree  = $get('currentdegree');
                        $degree_pursuing = $get('degreepursuing');
                        $phone           = $get('phone');
                        $wechat          = $get('wechat');
                        $department      = $get('department');
                        $workplace       = $get('workplace');
                        $homeplace       = $get('homeplace');
                        $status          = $get('status');
                        if ($status === '' || $status === null) { $status = 'in_work'; }

                        // Light validation: need at least one unique key
                        if ($campus_id === '' && $email === '') { $skipped++; continue; }

                        // Normalize numeric year if set
                        if ($year_of_join === '' || $year_of_join === null) {
                            $year_of_join = null;
                        } else {
                            $year_of_join = (int)$year_of_join;
                        }

                        try {
                            $stmt->execute([
                                $campus_id, $name, $email, $identity_number, $year_of_join,
                                $current_degree, $degree_pursuing, $phone, $wechat,
                                $department, $workplace, $homeplace, $status
                            ]);

                            // Rowcount is 1 for insert, 2 for update when using ODKU
                            $aff = $stmt->rowCount();
                            if ($aff === 1) $imported++;
                            elseif ($aff === 2) $updated++;
                            else $imported++; // some drivers always return 1

                        } catch (Throwable $e) {
                            // Skip row but keep going
                            // error_log("Row $rownum failed: ".$e->getMessage());
                            $skipped++;
                            continue;
                        }
                    }

                    fclose($fp);
                    $pdo->commit();

                    $status = [
                        'type' => 'success',
                        'msg'  => "Import finished. Inserted: $imported, Updated: $updated, Skipped: $skipped."
                    ];
                }
            }
        }
    }
}


include 'header.php';
?>
<h2 data-i18n="members_import.title">Import Members from Excel (CSV)</h2>
<form method="post" enctype="multipart/form-data">
  <div class="mb-3">
    <input type="file" name="file" accept=".csv" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary" data-i18n="members_import.import">Import</button>
  <a href="members.php" class="btn btn-secondary" data-i18n="members_import.cancel">Cancel</a>
</form>
<?php include 'footer.php'; ?>
