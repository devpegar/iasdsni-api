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
$slug = normalize_page_slug($data["slug"] ?? "");
$title = trim($data["title"] ?? "");
$metaDescription = trim($data["meta_description"] ?? "");
$content = $data["content"] ?? null;
$isActive = isset($data["is_active"]) && (int)$data["is_active"] === 0 ? 0 : 1;

if ($slug === "" || $title === "") {
    json_error("Slug y título son obligatorios", 422);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM pages WHERE slug = :slug LIMIT 1");
    $stmt->execute(["slug" => $slug]);

    if ($stmt->fetch()) {
        json_error("Ya existe una página con ese slug", 409);
    }

    $stmt = $pdo->prepare("
        INSERT INTO pages (slug, title, meta_description, content, is_active)
        VALUES (:slug, :title, :meta_description, :content, :is_active)
    ");

    $stmt->execute([
        "slug" => $slug,
        "title" => $title,
        "meta_description" => $metaDescription !== "" ? $metaDescription : null,
        "content" => $content !== null ? (string)$content : null,
        "is_active" => $isActive,
    ]);

    json_success([
        "id" => (int)$pdo->lastInsertId(),
        "slug" => $slug,
    ], "Página creada correctamente", 201);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al crear página");
}
