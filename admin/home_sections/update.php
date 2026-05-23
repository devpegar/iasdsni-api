<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("POST");
require_role(["admin"]);

$allowedKeys = [
    "hero_carousel",
    "verse_daily",
    "mission_vision_service",
    "adventists_world",
    "gallery",
    "contact_map",
    "latest_news",
];

$data = read_json_body();
$id = (int)($data["id"] ?? 0);

if (!$id) {
    json_error("ID inválido", 422);
}

try {
    $stmt = $pdo->prepare("
        SELECT id, section_key, title, subtitle, config_json, sort_order, is_active
        FROM home_sections
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(["id" => $id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current) {
        json_error("La sección no existe", 404);
    }

    $sectionKey = array_key_exists("section_key", $data)
        ? trim((string)$data["section_key"])
        : $current["section_key"];
    $title = array_key_exists("title", $data)
        ? trim((string)$data["title"])
        : $current["title"];
    $subtitle = array_key_exists("subtitle", $data)
        ? trim((string)$data["subtitle"])
        : $current["subtitle"];
    $configJson = array_key_exists("config_json", $data)
        ? trim((string)$data["config_json"])
        : $current["config_json"];
    $sortOrder = array_key_exists("sort_order", $data)
        ? (is_numeric($data["sort_order"]) ? (int)$data["sort_order"] : null)
        : (int)$current["sort_order"];
    $isActive = array_key_exists("is_active", $data)
        ? ((int)$data["is_active"] ? 1 : 0)
        : (int)$current["is_active"];

    if ($sectionKey === "") {
        json_error("section_key es obligatorio", 422);
    }

    if (!in_array($sectionKey, $allowedKeys, true)) {
        json_error("section_key inválido", 422);
    }

    if ($sortOrder === null) {
        json_error("Orden inválido", 422);
    }

    if ($configJson !== null && $configJson !== "") {
        json_decode($configJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            json_error("config_json debe ser JSON válido", 422);
        }
    }

    $stmt = $pdo->prepare("
        UPDATE home_sections
        SET section_key = :section_key,
            title = :title,
            subtitle = :subtitle,
            config_json = :config_json,
            sort_order = :sort_order,
            is_active = :is_active
        WHERE id = :id
    ");

    $stmt->execute([
        "section_key" => $sectionKey,
        "title" => $title !== "" ? $title : null,
        "subtitle" => $subtitle !== "" ? $subtitle : null,
        "config_json" => $configJson !== "" ? $configJson : null,
        "sort_order" => $sortOrder,
        "is_active" => $isActive,
        "id" => $id,
    ]);

    json_success(["id" => $id, "is_active" => $isActive], "Sección actualizada correctamente");
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al actualizar sección");
}
