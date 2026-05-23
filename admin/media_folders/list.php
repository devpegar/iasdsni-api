<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("GET");
require_role(["admin"]);

try {
    $stmt = $pdo->query("
        SELECT id, name, slug, sort_order, is_active, created_at
        FROM media_folders
        WHERE is_active = 1
        ORDER BY sort_order ASC, name ASC, id ASC
    ");

    $folders = array_map(function ($folder) {
        return [
            "id" => (int)$folder["id"],
            "name" => $folder["name"],
            "slug" => $folder["slug"],
            "sort_order" => (int)$folder["sort_order"],
            "is_active" => (int)$folder["is_active"],
            "created_at" => $folder["created_at"],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success(["folders" => $folders]);
} catch (PDOException $e) {
    if ($e->getCode() === "42S02") {
        json_success(["folders" => []]);
    }

    error_log($e->getMessage());
    json_error("Error al obtener carpetas multimedia");
}
