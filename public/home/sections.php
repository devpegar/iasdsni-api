<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";

require_method("GET");

try {
    $stmt = $pdo->query("
        SELECT id, section_key, title, subtitle, config_json, sort_order
        FROM home_sections
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");

    $sections = array_map(function ($section) {
        $config = [];

        if ($section["config_json"] !== null && trim($section["config_json"]) !== "") {
            $decoded = json_decode($section["config_json"], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $config = $decoded;
            } else {
                error_log("[home/sections] invalid config_json for section_id=" . $section["id"]);
            }
        }

        return [
            "id" => (int)$section["id"],
            "section_key" => $section["section_key"],
            "title" => $section["title"],
            "subtitle" => $section["subtitle"],
            "config" => $config,
            "sort_order" => (int)$section["sort_order"],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success(["data" => $sections]);
} catch (PDOException $e) {
    if ($e->getCode() === "42S02") {
        error_log("[home/sections] home_sections table missing");
        json_success(["data" => []]);
    }

    error_log($e->getMessage());
    json_error("Error al obtener secciones del home");
}
