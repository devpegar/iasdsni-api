<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";

require_method("GET");

try {
    $stmt = $pdo->query("
        SELECT id, label, url, target, sort_order
        FROM navigation_items
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");

    $items = array_map(function ($item) {
        return [
            "id" => (int)$item["id"],
            "label" => $item["label"],
            "url" => $item["url"],
            "target" => $item["target"],
            "sort_order" => (int)$item["sort_order"],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success(["data" => $items]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener navegación");
}
