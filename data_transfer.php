<?php

function format_sql_value(PDO $pdo, $value): string
{
    if ($value === null) {
        return 'NULL';
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    return $pdo->quote((string) $value);
}

function create_database_dump(PDO $pdo): string
{
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $sql = "-- Exported on " . date('Y-m-d H:i:s') . "\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";

    foreach ($tables as $table) {
        $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $createSql = $createStmt['Create Table'] ?? '';
        if (!$createSql) {
            continue;
        }
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $createSql . ";\n";

        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            continue;
        }
        $columns = array_map(static fn($col) => "`{$col}`", array_keys($rows[0]));
        $columnList = implode(', ', $columns);

        foreach ($rows as $row) {
            $values = array_map(static fn($value) => format_sql_value($pdo, $value), array_values($row));
            $valueList = implode(', ', $values);
            $sql .= "INSERT INTO `$table` ({$columnList}) VALUES ({$valueList});\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}

function split_sql_statements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $inString = false;
    $stringChar = '';
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($inString) {
            $buffer .= $char;
            if ($char === $stringChar) {
                $escaped = false;
                $backIndex = $i - 1;
                while ($backIndex >= 0 && $sql[$backIndex] === '\\') {
                    $escaped = !$escaped;
                    $backIndex--;
                }
                if (!$escaped) {
                    $inString = false;
                    $stringChar = '';
                }
            }
            continue;
        }

        if ($char === '"' || $char === "'") {
            $inString = true;
            $stringChar = $char;
            $buffer .= $char;
            continue;
        }

        if ($char === '-' && $next === '-') {
            $nextChar = $i + 2 < $length ? $sql[$i + 2] : '';
            if ($nextChar === ' ' || $nextChar === "\t" || $nextChar === "\r" || $nextChar === "\n") {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }
        }

        if ($char === '#') {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            continue;
        }

        if ($char === '/' && $next === '*') {
            $i += 2;
            while ($i < $length - 1 && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                $i++;
            }
            $i++;
            continue;
        }

        if ($char === ';') {
            $trimmed = trim($buffer);
            if ($trimmed !== '') {
                $statements[] = $trimmed;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $trimmed = trim($buffer);
    if ($trimmed !== '') {
        $statements[] = $trimmed;
    }

    return $statements;
}

function import_database_dump(PDO $pdo, string $sql): void
{
    $statements = split_sql_statements($sql);
    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
}

function add_directory_to_zip(ZipArchive $zip, string $source, string $zipPath): void
{
    if (!is_dir($source)) {
        return;
    }
    $source = rtrim($source, DIRECTORY_SEPARATOR);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relativePath = substr($item->getPathname(), strlen($source) + 1);
        $targetPath = $zipPath . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
        if ($item->isDir()) {
            $zip->addEmptyDir($targetPath);
        } else {
            $zip->addFile($item->getPathname(), $targetPath);
        }
    }
}

function remove_directory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }
    @rmdir($dir);
}

function copy_directory(string $source, string $destination): void
{
    if (!is_dir($source)) {
        return;
    }
    if (!is_dir($destination)) {
        mkdir($destination, 0777, true);
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $targetPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        if ($item->isDir()) {
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0777, true);
            }
        } else {
            copy($item->getPathname(), $targetPath);
        }
    }
}

function safe_extract_zip(ZipArchive $zip, string $destination): bool
{
    if (!is_dir($destination)) {
        mkdir($destination, 0777, true);
    }
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if ($name === false || $name === '') {
            continue;
        }
        if (str_starts_with($name, '/') || str_contains($name, '..')) {
            continue;
        }
        $target = $destination . DIRECTORY_SEPARATOR . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $name);
        if (str_ends_with($name, '/')) {
            if (!is_dir($target)) {
                mkdir($target, 0777, true);
            }
            continue;
        }
        $dir = dirname($target);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $contents = $zip->getFromIndex($i);
        if ($contents === false) {
            continue;
        }
        file_put_contents($target, $contents);
    }
    return true;
}
