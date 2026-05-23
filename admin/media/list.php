<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("GET");
require_role(["admin"]);

try {
    $stmt = $pdo->query("
        SELECT id, filename, original_name, mime_type, size_bytes, path, public_url, alt_text, is_active, created_at
        FROM media_files
        WHERE is_active = 1
        ORDER BY created_at DESC, id DESC
    ");

    $files = array_map(function ($file) {
        return [
            "id" => (int)$file["id"],
            "filename" => $file["filename"],
            "original_name" => $file["original_name"],
            "mime_type" => $file["mime_type"],
            "size_bytes" => (int)$file["size_bytes"],
            "path" => $file["path"],
            "public_url" => $file["public_url"],
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
