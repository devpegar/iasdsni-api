<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

function normalize_page_slug($slug)
{
    $slug = strtolower(trim($slug));

    if (function_exists("iconv")) {
        $converted = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $slug);
        if ($converted !== false) {
            $slug = $converted;
        }
    }

    $slug = preg_replace("/[^a-z0-9]+/", "-", $slug);
    return trim($slug, "-");
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
        SELECT id, slug, title, meta_description, content, is_active
        FROM pages
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(["id" => $id]);
    $currentPage = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentPage) {
        json_error("La página no existe", 404);
    }

    $slug = array_key_exists("slug", $data)
        ? normalize_page_slug($data["slug"])
        : $currentPage["slug"];
    $title = array_key_exists("title", $data)
        ? trim($data["title"])
        : $currentPage["title"];
    $metaDescription = array_key_exists("meta_description", $data)
        ? trim((string)$data["meta_description"])
        : $currentPage["meta_description"];
    $content = array_key_exists("content", $data)
        ? ($data["content"] !== null ? (string)$data["content"] : null)
        : $currentPage["content"];
    $isActive = array_key_exists("is_active", $data)
        ? ((int)$data["is_active"] ? 1 : 0)
        : (int)$currentPage["is_active"];

    if ($slug === "" || $title === "") {
        json_error("Slug y título son obligatorios", 422);
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM pages
        WHERE slug = :slug AND id <> :id
        LIMIT 1
    ");
    $stmt->execute([
        "slug" => $slug,
        "id" => $id,
    ]);

    if ($stmt->fetch()) {
        json_error("Ya existe una página con ese slug", 409);
    }

    $stmt = $pdo->prepare("
        UPDATE pages
        SET slug = :slug,
            title = :title,
            meta_description = :meta_description,
            content = :content,
            is_active = :is_active
        WHERE id = :id
    ");

    $stmt->execute([
        "slug" => $slug,
        "title" => $title,
        "meta_description" => $metaDescription !== "" ? $metaDescription : null,
        "content" => $content,
        "is_active" => $isActive,
        "id" => $id,
    ]);

    json_success([
        "id" => $id,
        "slug" => $slug,
        "is_active" => $isActive,
    ], "Página actualizada correctamente");
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al actualizar página");
}
