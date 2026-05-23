<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";

require_method("GET");

$slug = trim($_GET["slug"] ?? "");

if ($slug === "") {
    json_error("Debe indicar una página", 400);
}

try {
    $selectSeoColumns = true;
    $sql = "
        SELECT id, slug, title, meta_description, content, created_at, updated_at,
               seo_title, og_image, canonical_url, noindex
        FROM pages
        WHERE slug = :slug AND is_active = 1
        LIMIT 1
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["slug" => $slug]);
    } catch (PDOException $e) {
        if ($e->getCode() !== "42S22") {
            throw $e;
        }

        $selectSeoColumns = false;
        $stmt = $pdo->prepare("
            SELECT id, slug, title, meta_description, content, created_at, updated_at
            FROM pages
            WHERE slug = :slug AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute(["slug" => $slug]);
    }

    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$page) {
        json_error("Página no encontrada", 404);
    }

    $data = [
        "id" => (int)$page["id"],
        "slug" => $page["slug"],
        "title" => $page["title"],
        "meta_description" => $page["meta_description"],
        "content" => $page["content"],
        "created_at" => $page["created_at"],
        "updated_at" => $page["updated_at"],
    ];

    if ($selectSeoColumns) {
        $data["seo_title"] = $page["seo_title"];
        $data["og_image"] = $page["og_image"];
        $data["canonical_url"] = $page["canonical_url"];
        $data["noindex"] = (int)$page["noindex"];
    }

    json_success(["data" => $data]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener página");
}
