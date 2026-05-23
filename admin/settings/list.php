<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("GET");
require_role(["admin"]);

try {
    $stmt = $pdo->query("
        SELECT id, setting_key, setting_value, setting_type, group_name, label, sort_order, is_public, created_at, updated_at
        FROM site_settings
        ORDER BY group_name ASC, sort_order ASC, id ASC
    ");

    $groups = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $groupName = $row["group_name"];

        if (!isset($groups[$groupName])) {
            $groups[$groupName] = [];
        }

        $groups[$groupName][] = [
            "id" => (int)$row["id"],
            "setting_key" => $row["setting_key"],
            "setting_value" => $row["setting_value"] ?? "",
            "setting_type" => $row["setting_type"],
            "group_name" => $row["group_name"],
            "label" => $row["label"],
            "sort_order" => (int)$row["sort_order"],
            "is_public" => (int)$row["is_public"],
            "created_at" => $row["created_at"],
            "updated_at" => $row["updated_at"],
        ];
    }

    json_success(["groups" => $groups]);
} catch (PDOException $e) {
    if ($e->getCode() === "42S02") {
        json_success(["groups" => []]);
    }

    error_log($e->getMessage());
    json_error("Error al obtener configuración del sitio");
}
