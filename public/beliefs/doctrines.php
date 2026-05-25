<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";

require_method("GET");

try {
    $stmt = $pdo->query("
        SELECT id, title, slug, summary, image_url, sort_order, is_active, created_at, updated_at
        FROM belief_doctrines
        WHERE is_active = 1
        ORDER BY sort_order ASC, title ASC
    ");

    $doctrines = array_map(function ($row) {
        return [
            "id" => (int)$row["id"],
            "title" => $row["title"],
            "slug" => $row["slug"],
            "summary" => $row["summary"],
            "image_url" => $row["image_url"],
            "sort_order" => (int)$row["sort_order"],
            "created_at" => $row["created_at"],
            "updated_at" => $row["updated_at"],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success(["data" => $doctrines]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener creencias");
}
