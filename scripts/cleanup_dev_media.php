<?php

require_once __DIR__ . "/../utils/env.php";

load_env(__DIR__ . "/../.env");

$isRealRun = in_array("--yes", $argv, true);
$appEnv = strtolower((string)env("APP_ENV", "local"));
$mediaDir = realpath(__DIR__ . "/../uploads/media");
$apiRoot = realpath(__DIR__ . "/..");
$protectedNames = [".gitkeep" => true, ".htaccess" => true];
$tables = ["media_files", "gallery_albums", "gallery_album_items"];

function cleanup_print_line($message = "")
{
    echo $message . PHP_EOL;
}

function cleanup_fail($message, $code = 1)
{
    fwrite(STDERR, $message . PHP_EOL);
    exit($code);
}

function cleanup_relative_path($path, $root)
{
    $relative = str_replace("\\", "/", substr($path, strlen($root) + 1));
    return $relative === "" ? "." : $relative;
}

function cleanup_connect_pdo()
{
    $host = env("DB_HOST");
    $dbname = env("DB_NAME");
    $username = env("DB_USER");
    $password = env("DB_PASS");

    if (!$host || !$dbname || !$username) {
        cleanup_fail("DB_HOST, DB_NAME y DB_USER son obligatorios en .env");
    }

    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    return $pdo;
}

function cleanup_table_exists($pdo, $table)
{
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE :table_name");
        $stmt->execute(["table_name" => $table]);
        return (bool)$stmt->fetch(PDO::FETCH_NUM);
    } catch (PDOException $e) {
        return false;
    }
}

function cleanup_count_rows($pdo, $table)
{
    if (!cleanup_table_exists($pdo, $table)) {
        return null;
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
    return (int)$stmt->fetchColumn();
}

function cleanup_find_media_files($mediaDir, $apiRoot, $protectedNames)
{
    if (!$mediaDir || !is_dir($mediaDir)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($mediaDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $filename = $fileInfo->getFilename();

        if (isset($protectedNames[$filename])) {
            continue;
        }

        $files[] = [
            "path" => $fileInfo->getPathname(),
            "relative" => cleanup_relative_path($fileInfo->getPathname(), $apiRoot),
            "size" => $fileInfo->getSize(),
        ];
    }

    usort($files, function ($a, $b) {
        return strcmp($a["relative"], $b["relative"]);
    });

    return $files;
}

function cleanup_format_size($bytes)
{
    if ($bytes < 1024) {
        return $bytes . " B";
    }

    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . " KB";
    }

    return round($bytes / (1024 * 1024), 2) . " MB";
}

if (PHP_SAPI !== "cli") {
    cleanup_fail("Este script solo puede ejecutarse por CLI.");
}

if ($appEnv === "production") {
    cleanup_fail("Abortado: APP_ENV=production. Este script es solo para local/dev.");
}

$pdo = cleanup_connect_pdo();
$counts = [];

foreach ($tables as $table) {
    $counts[$table] = cleanup_count_rows($pdo, $table);
}

$mediaFiles = cleanup_find_media_files($mediaDir, $apiRoot, $protectedNames);

cleanup_print_line("Cleanup dev Media Library/Galeria");
cleanup_print_line("APP_ENV: " . $appEnv);
cleanup_print_line("Modo: " . ($isRealRun ? "REAL (--yes)" : "DRY-RUN"));
cleanup_print_line("");
cleanup_print_line("Registros encontrados:");

foreach ($counts as $table => $count) {
    cleanup_print_line("- {$table}: " . ($count === null ? "tabla no existe" : $count));
}

cleanup_print_line("");
cleanup_print_line("Archivos que se eliminarian en uploads/media/:");

if (count($mediaFiles) === 0) {
    cleanup_print_line("- Ninguno");
} else {
    foreach ($mediaFiles as $file) {
        cleanup_print_line("- " . $file["relative"] . " (" . cleanup_format_size($file["size"]) . ")");
    }
}

cleanup_print_line("");
cleanup_print_line("Protegidos: .gitkeep y .htaccess se conservan. No se toca uploads/hero ni uploads/noticias.");

if (!$isRealRun) {
    cleanup_print_line("");
    cleanup_print_line("Dry-run: no se borro nada.");
    cleanup_print_line("Para ejecutar el borrado real, confirmar primero y correr: php scripts/cleanup_dev_media.php --yes");
    exit(0);
}

$deletedRows = [];
$deletedFiles = 0;

try {
    $pdo->beginTransaction();

    foreach (["gallery_album_items", "gallery_albums", "media_files"] as $table) {
        if (!cleanup_table_exists($pdo, $table)) {
            $deletedRows[$table] = null;
            continue;
        }

        $deletedRows[$table] = $pdo->exec("DELETE FROM {$table}");
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    cleanup_fail("Error al borrar registros: " . $e->getMessage());
}

foreach ($mediaFiles as $file) {
    if (is_file($file["path"]) && unlink($file["path"])) {
        $deletedFiles++;
    }
}

cleanup_print_line("");
cleanup_print_line("Resumen:");

foreach ($deletedRows as $table => $count) {
    cleanup_print_line("- {$table}: " . ($count === null ? "tabla no existe" : $count . " registros borrados"));
}

cleanup_print_line("- archivos borrados: " . $deletedFiles);
cleanup_print_line("");
cleanup_print_line("Listo. Volver a subir imagenes desde Admin -> Sitio Web -> Multimedia.");
