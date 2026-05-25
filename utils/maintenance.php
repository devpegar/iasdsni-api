<?php

function maintenance_flag_path()
{
    $configuredPath = env('MAINTENANCE_FLAG_PATH');

    if ($configuredPath) {
        return $configuredPath;
    }

    $frontendPublic = realpath(__DIR__ . '/../../iasdsni-reactjs/public');

    if ($frontendPublic) {
        return $frontendPublic . '/maintenance.flag';
    }

    return __DIR__ . '/../maintenance.flag';
}

function maintenance_flag_exists()
{
    return file_exists(maintenance_flag_path());
}

function maintenance_enable_flag()
{
    $path = maintenance_flag_path();
    $directory = dirname($path);

    if (!is_dir($directory)) {
        error_log("[maintenance] flag directory does not exist: {$directory}");
        return false;
    }

    if (!is_writable($directory)) {
        error_log("[maintenance] flag directory is not writable: {$directory}");
        return false;
    }

    $content = "enabled_at=" . gmdate('c') . PHP_EOL;
    $written = @file_put_contents($path, $content, LOCK_EX);

    if ($written === false) {
        error_log("[maintenance] unable to write flag: {$path}");
        return false;
    }

    return true;
}

function maintenance_disable_flag()
{
    $path = maintenance_flag_path();

    if (!file_exists($path)) {
        return true;
    }

    if (!is_writable($path) && !is_writable(dirname($path))) {
        error_log("[maintenance] flag is not writable: {$path}");
        return false;
    }

    if (!@unlink($path)) {
        error_log("[maintenance] unable to delete flag: {$path}");
        return false;
    }

    return true;
}

function maintenance_db_connection()
{
    $host = env('DB_HOST');
    $dbname = env('DB_NAME');
    $username = env('DB_USER');
    $password = env('DB_PASS');

    if (!$host || !$dbname || !$username) {
        return null;
    }

    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $username,
            $password
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    } catch (Throwable $e) {
        error_log("[maintenance] DB unavailable: " . $e->getMessage());
        return null;
    }
}

function maintenance_db_state()
{
    $pdo = maintenance_db_connection();

    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->query("SELECT maintenance FROM settings WHERE id = 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (bool)($row["maintenance"] ?? false);
    } catch (Throwable $e) {
        error_log("[maintenance] unable to read DB state: " . $e->getMessage());
        return null;
    }
}

function maintenance_update_db_state($enabled)
{
    $pdo = maintenance_db_connection();

    if (!$pdo) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("UPDATE settings SET maintenance = ? WHERE id = 1");
        $stmt->execute([$enabled ? 1 : 0]);

        return true;
    } catch (Throwable $e) {
        error_log("[maintenance] unable to update DB state: " . $e->getMessage());
        return false;
    }
}
