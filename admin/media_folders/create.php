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
$name = trim((string)($data["name"] ?? ""));
$slug = normalize_media_folder_slug($data["slug"] ?? $name);
$sortOrder = is_numeric($data["sort_order"] ?? null) ? (int)$data["sort_order"] : 0;

if ($name === "") {
    json_error("Nombre obligatorio", 422);
}

if ($slug === "") {
    json_error("Slug inválido", 422);
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO media_folders (name, slug, sort_order, is_active)
        VALUES (:name, :slug, :sort_order, 1)
    ");
    $stmt->execute([
        "name" => $name,
        "slug" => $slug,
        "sort_order" => $sortOrder,
    ]);

    json_success([
        "id" => (int)$pdo->lastInsertId(),
        "slug" => $slug,
    ], "Carpeta creada correctamente", 201);
} catch (PDOException $e) {
    if ($e->getCode() === "23000") {
        json_error("Ya existe una carpeta con ese slug", 409);
    }

    error_log($e->getMessage());
    json_error("Error al crear carpeta multimedia");
}
