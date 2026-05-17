<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("POST");
require_role(["admin"]);

$data = read_json_body();
$label = trim($data["label"] ?? "");
$url = trim($data["url"] ?? "");
$target = trim($data["target"] ?? "_self");
$sortOrder = is_numeric($data["sort_order"] ?? 0) ? (int)$data["sort_order"] : null;
$isActive = isset($data["is_active"]) && (int)$data["is_active"] === 0 ? 0 : 1;

if ($label === "" || $url === "") {
    json_error("Etiqueta y URL son obligatorias", 422);
}

if (!in_array($target, ["_self", "_blank"], true)) {
    json_error("Target inválido", 422);
}

if ($sortOrder === null) {
    json_error("Orden inválido", 422);
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO navigation_items (label, url, target, sort_order, is_active)
        VALUES (:label, :url, :target, :sort_order, :is_active)
    ");

    $stmt->execute([
        "label" => $label,
        "url" => $url,
        "target" => $target,
        "sort_order" => $sortOrder,
        "is_active" => $isActive,
    ]);

    json_success([
        "id" => (int)$pdo->lastInsertId(),
    ], "Ítem de menú creado correctamente", 201);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al crear ítem de menú");
}
