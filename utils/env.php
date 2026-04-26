<?php

function load_env($path = __DIR__ . '/../.env')
{
    if (!file_exists($path)) {
        error_log("[env] .env not found at {$path}");
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) continue; // comentarios
        if (!str_contains($line, '=')) continue;

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key, " \t\n\r\0\x0B\xEF\xBB\xBF");
        $value = trim($value);

        if ($key === '') continue;

        if (
            strlen($value) >= 2 &&
            (($value[0] === '"' && substr($value, -1) === '"') ||
             ($value[0] === "'" && substr($value, -1) === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    $jwtSecret = env('JWT_SECRET');
    error_log("[env] loaded {$path}; JWT_SECRET " . ($jwtSecret ? "present len=" . strlen($jwtSecret) : "missing"));
}

function env($key, $default = null)
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?? $default;
}
