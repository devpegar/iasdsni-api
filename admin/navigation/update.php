<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("POST");
require_role(["admin"]);

$data = read_json_body();
$id = (int)($data["id"] ?? 0);

if (!$id) {
    json_error("ID inválido", 422);
}

try {
    $stmt = $pdo->prepare("
        SELECT id, label, url, target, sort_order, is_active
        FROM navigation_items
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(["id" => $id]);
    $currentItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentItem) {
        json_error("El ítem de menú no existe", 404);
    }

    $label = array_key_exists("label", $data)
        ? trim((string)$data["label"])
        : $currentItem["label"];
    $url = array_key_exists("url", $data)
        ? trim((string)$data["url"])
        : $currentItem["url"];
    $target = array_key_exists("target", $data)
        ? trim((string)$data["target"])
        : $currentItem["target"];
    $sortOrder = array_key_exists("sort_order", $data)
        ? (is_numeric($data["sort_order"]) ? (int)$data["sort_order"] : null)
        : (int)$currentItem["sort_order"];
    $isActive = array_key_exists("is_active", $data)
        ? ((int)$data["is_active"] ? 1 : 0)
        : (int)$currentItem["is_active"];

    if ($label === "" || $url === "") {
        json_error("Etiqueta y URL son obligatorias", 422);
    }

    if (!in_array($target, ["_self", "_blank"], true)) {
        json_error("Target inválido", 422);
    }

    if ($sortOrder === null) {
        json_error("Orden inválido", 422);
    }

    $stmt = $pdo->prepare("
        UPDATE navigation_items
        SET label = :label,
            url = :url,
            target = :target,
            sort_order = :sort_order,
            is_active = :is_active
        WHERE id = :id
    ");

    $stmt->execute([
        "label" => $label,
        "url" => $url,
        "target" => $target,
        "sort_order" => $sortOrder,
        "is_active" => $isActive,
        "id" => $id,
    ]);

    json_success([
        "id" => $id,
        "is_active" => $isActive,
    ], "Ítem de menú actualizado correctamente");
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al actualizar ítem de menú");
}
