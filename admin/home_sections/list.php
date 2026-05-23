<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("GET");
require_role(["admin"]);

try {
    $stmt = $pdo->query("
        SELECT id, section_key, title, subtitle, config_json, sort_order, is_active, created_at, updated_at
        FROM home_sections
        ORDER BY sort_order ASC, id ASC
    ");

    $sections = array_map(function ($section) {
        return [
            "id" => (int)$section["id"],
            "section_key" => $section["section_key"],
            "title" => $section["title"],
            "subtitle" => $section["subtitle"],
            "config_json" => $section["config_json"],
            "sort_order" => (int)$section["sort_order"],
            "is_active" => (int)$section["is_active"],
            "created_at" => $section["created_at"],
            "updated_at" => $section["updated_at"],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success(["home_sections" => $sections]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener secciones del home");
}
