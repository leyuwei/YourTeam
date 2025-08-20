
<?php
    header('Content-Type: application/json; charset=utf-8');
    include 'connection.php';

    // Max upload size: 10 MB
    define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024);

    // 1. Quick size check
    if (
        isset($_POST['checkerpaper'], $_FILES['file'])
        && $_POST['checkerpaper'] === 'false'
        && $_FILES['file']['size'] >= MAX_UPLOAD_SIZE
    ) {
        http_response_code(400);
        die(json_encode([
            'filename' => $_FILES['file']['name'],
            'filesize' => $_FILES['file']['size'],
            'result'   => '0',
            'content'  => '无效文件！ - 发票尺寸超限'
        ], JSON_UNESCAPED_UNICODE));
    }

    // 2. Check for PHP upload errors
    if (
        isset($_FILES['file'])
        && $_POST['checkerpaper'] === 'false'
        && $_FILES['file']['error'] > 0
    ) {
        http_response_code(400);
        die(json_encode([
            'filename' => $_FILES['file']['name'],
            'filesize' => $_FILES['file']['size'],
            'result'   => '0',
            'content'  => '发票初步校验过程遇到错误: ' . $_FILES['file']['error']
        ], JSON_UNESCAPED_UNICODE));
    }

    // 3. If this is a paper invoice, skip processing
    if (isset($_POST['checkerpaper']) && $_POST['checkerpaper'] !== 'false') {
        echo json_encode([
            'filename' => 'ischeckerpaper',
            'result'   => '1',
            'content'  => '纸质发票无需校验',
            'parse'    => ''
        ], JSON_UNESCAPED_UNICODE);

        $conn->close();
        exit;
    }

    // 4. At this point we have a valid upload: enforce PDF only
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        die(json_encode([
            'result'  => '0',
            'content' => '未检测到上传文件'
        ], JSON_UNESCAPED_UNICODE));
    }

    // Verify MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['file']['tmp_name']);
    if ($mime !== 'application/pdf') {
        http_response_code(400);
        die(json_encode([
            'result'  => '0',
            'content' => '无效文件类型，必须为 PDF'
        ], JSON_UNESCAPED_UNICODE));
    }

    // 5. Build a safe filename
    //    Use only your own data; do not trust the client-provided name directly
    $username   = preg_replace('/[^a-zA-Z0-9_-]/', '', ($_POST['username']   ?? 'user'));
    $usernumber = preg_replace('/[^a-zA-Z0-9_-]/', '', ($_POST['usernumber'] ?? ''));
    $batch      = intval($_POST['batch'] ?? 1);
    $random     = bin2hex(random_bytes(8));
    $newName    = sprintf('%s_%s_%d_%s.pdf', $username, $usernumber, $batch, $random);

    // 6. Move into place
    $uploadDir = '/var/www/html/uploads/';
    if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
        http_response_code(500);
        die(json_encode([
            'result'  => '0',
            'content' => '服务器上传目录不可用'
        ], JSON_UNESCAPED_UNICODE));
    }

    $targetPath = $uploadDir . $newName;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        http_response_code(500);
        die(json_encode([
            'result'  => '0',
            'content' => '文件在服务器上暂存失败'
        ], JSON_UNESCAPED_UNICODE));
    }

    // 7. Extract text safely
    $escapedPath = escapeshellarg($targetPath);
    $command     = "LANG=zh_CN.UTF-8 pdftotext {$escapedPath} - 2>&1";
    $output      = shell_exec($command);

    // 8. Return JSON result
    echo json_encode([
        'filename' => $newName,
        'filesize' => $_FILES['file']['size'],
        'result'   => '1',
        'content'  => '校验成功',
        'parse'    => $output
    ], JSON_UNESCAPED_UNICODE);

    $conn->close();
?>
