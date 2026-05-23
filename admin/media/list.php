<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("GET");
require_role(["admin"]);

function media_table_exists($pdo, $table)
{
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE :table_name");
        $stmt->execute(["table_name" => $table]);
        return (bool)$stmt->fetch(PDO::FETCH_NUM);
    } catch (PDOException $e) {
        return false;
    }
}

function media_column_exists($pdo, $table, $column)
{
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE :column_name");
        $stmt->execute(["column_name" => $column]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

try {
    $hasFolders = media_table_exists($pdo, "media_folders") && media_column_exists($pdo, "media_files", "folder_id");
    $optionalColumns = [
        "original_url",
        "optimized_url",
        "thumbnail_url",
        "width",
        "height",
        "optimized_width",
        "optimized_height",
        "optimization_status",
    ];
    $availableOptionalColumns = array_filter($optionalColumns, function ($column) use ($pdo) {
        return media_column_exists($pdo, "media_files", $column);
    });
    $optionalSelect = "";

    if (count($availableOptionalColumns) > 0) {
        $optionalSelect = ", mf." . implode(", mf.", $availableOptionalColumns);
    }

    if ($hasFolders) {
        $stmt = $pdo->query("
            SELECT mf.id, mf.folder_id, mf.filename, mf.original_name, mf.mime_type, mf.size_bytes, mf.path,
                   mf.public_url, mf.alt_text, mf.is_active, mf.created_at{$optionalSelect},
                   f.name AS folder_name, f.slug AS folder_slug
            FROM media_files mf
            LEFT JOIN media_folders f ON f.id = mf.folder_id
            WHERE mf.is_active = 1
            ORDER BY mf.created_at DESC, mf.id DESC
        ");
    } else {
        $optionalSelectNoAlias = "";

        if (count($availableOptionalColumns) > 0) {
            $optionalSelectNoAlias = ", " . implode(", ", $availableOptionalColumns);
        }

        $stmt = $pdo->query("
            SELECT id, filename, original_name, mime_type, size_bytes, path, public_url, alt_text, is_active, created_at{$optionalSelectNoAlias}
            FROM media_files
            WHERE is_active = 1
            ORDER BY created_at DESC, id DESC
        ");
    }

    $files = array_map(function ($file) {
        $publicUrl = $file["optimized_url"] ?? $file["public_url"];

        return [
            "id" => (int)$file["id"],
            "folder_id" => isset($file["folder_id"]) ? (int)$file["folder_id"] : null,
            "folder_name" => $file["folder_name"] ?? null,
            "folder_slug" => $file["folder_slug"] ?? null,
            "filename" => $file["filename"],
            "original_name" => $file["original_name"],
            "mime_type" => $file["mime_type"],
            "size_bytes" => (int)$file["size_bytes"],
            "path" => $file["path"],
            "public_url" => $publicUrl,
            "original_url" => $file["original_url"] ?? null,
            "optimized_url" => $file["optimized_url"] ?? null,
            "thumbnail_url" => $file["thumbnail_url"] ?? null,
            "width" => isset($file["width"]) ? (int)$file["width"] : null,
            "height" => isset($file["height"]) ? (int)$file["height"] : null,
            "optimized_width" => isset($file["optimized_width"]) ? (int)$file["optimized_width"] : null,
            "optimized_height" => isset($file["optimized_height"]) ? (int)$file["optimized_height"] : null,
            "optimization_status" => $file["optimization_status"] ?? "legacy",
            "alt_text" => $file["alt_text"] ?? "",
            "is_active" => (int)$file["is_active"],
            "created_at" => $file["created_at"],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success(["media_files" => $files]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener archivos multimedia");
}
