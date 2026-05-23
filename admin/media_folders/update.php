<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

function normalize_media_folder_slug($value)
{
    $value = strtolower(trim((string)$value));

    if (function_exists("iconv")) {
        $converted = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = preg_replace("/[^a-z0-9]+/", "-", $value);
    return trim($value, "-");
}

require_method("POST");
require_role(["admin"]);

$data = read_json_body();
$id = (int)($data["id"] ?? 0);

if (!$id) {
    json_error("ID inválido", 422);
}

try {
    $stmt = $pdo->prepare("
        SELECT id, name, slug, sort_order
        FROM media_folders
        WHERE id = :id AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute(["id" => $id]);
    $currentFolder = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentFolder) {
        json_error("Carpeta no encontrada", 404);
    }

    $name = array_key_exists("name", $data)
        ? trim((string)$data["name"])
        : $currentFolder["name"];
    $slug = array_key_exists("slug", $data)
        ? normalize_media_folder_slug($data["slug"])
        : $currentFolder["slug"];
    $sortOrder = array_key_exists("sort_order", $data)
        ? (is_numeric($data["sort_order"]) ? (int)$data["sort_order"] : null)
        : (int)$currentFolder["sort_order"];

    if ($name === "") {
        json_error("Nombre obligatorio", 422);
    }

    if ($slug === "") {
        json_error("Slug inválido", 422);
    }

    if ($sortOrder === null) {
        json_error("Orden inválido", 422);
    }

    $stmt = $pdo->prepare("
        UPDATE media_folders
        SET name = :name,
            slug = :slug,
            sort_order = :sort_order
        WHERE id = :id
    ");
    $stmt->execute([
        "name" => $name,
        "slug" => $slug,
        "sort_order" => $sortOrder,
        "id" => $id,
    ]);

    json_success(["id" => $id, "slug" => $slug], "Carpeta actualizada correctamente");
} catch (PDOException $e) {
    if ($e->getCode() === "23000") {
        json_error("Ya existe una carpeta con ese slug", 409);
    }

    error_log($e->getMessage());
    json_error("Error al actualizar carpeta multimedia");
}
