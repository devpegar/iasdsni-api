<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";

require_method("GET");

try {
    $stmt = $pdo->query("
        SELECT id, public_url, alt_text
        FROM media_files
        WHERE is_active = 1
        ORDER BY created_at DESC, id DESC
    ");

    $files = array_map(function ($file) {
        return [
            "id" => (int)$file["id"],
            "public_url" => $file["public_url"],
            "alt_text" => $file["alt_text"] ?? "",
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success(["data" => $files]);
} catch (PDOException $e) {
    if ($e->getCode() === "42S02") {
        json_success(["data" => []]);
    }

    error_log($e->getMessage());
    json_error("Error al obtener multimedia");
}
