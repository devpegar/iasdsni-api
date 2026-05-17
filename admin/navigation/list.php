<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("GET");
require_role(["admin"]);

try {
    $stmt = $pdo->query("
        SELECT id, label, url, target, sort_order, is_active, created_at, updated_at
        FROM navigation_items
        ORDER BY sort_order ASC, id ASC
    ");

    $items = array_map(function ($item) {
        return [
            "id" => (int)$item["id"],
            "label" => $item["label"],
            "url" => $item["url"],
            "target" => $item["target"],
            "sort_order" => (int)$item["sort_order"],
            "is_active" => (int)$item["is_active"],
            "created_at" => $item["created_at"],
            "updated_at" => $item["updated_at"],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success(["navigation_items" => $items]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener navegación");
}
