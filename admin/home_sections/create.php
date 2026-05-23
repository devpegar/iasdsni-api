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
$sectionKey = trim($data["section_key"] ?? "");
$title = trim($data["title"] ?? "");
$subtitle = trim($data["subtitle"] ?? "");
$configJson = trim($data["config_json"] ?? "");
$sortOrder = is_numeric($data["sort_order"] ?? 0) ? (int)$data["sort_order"] : null;
$isActive = isset($data["is_active"]) && (int)$data["is_active"] === 0 ? 0 : 1;

if ($sectionKey === "") {
    json_error("section_key es obligatorio", 422);
}

if (!in_array($sectionKey, $allowedKeys, true)) {
    json_error("section_key inválido", 422);
}

if ($sortOrder === null) {
    json_error("Orden inválido", 422);
}

if ($configJson !== "") {
    json_decode($configJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_error("config_json debe ser JSON válido", 422);
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO home_sections (section_key, title, subtitle, config_json, sort_order, is_active)
        VALUES (:section_key, :title, :subtitle, :config_json, :sort_order, :is_active)
    ");

    $stmt->execute([
        "section_key" => $sectionKey,
        "title" => $title !== "" ? $title : null,
        "subtitle" => $subtitle !== "" ? $subtitle : null,
        "config_json" => $configJson !== "" ? $configJson : null,
        "sort_order" => $sortOrder,
        "is_active" => $isActive,
    ]);

    json_success(["id" => (int)$pdo->lastInsertId()], "Sección creada correctamente", 201);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al crear sección");
}
