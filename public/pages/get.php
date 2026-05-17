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
    $stmt = $pdo->prepare("
        SELECT id, slug, title, meta_description, content, created_at, updated_at
        FROM pages
        WHERE slug = :slug AND is_active = 1
        LIMIT 1
    ");

    $stmt->execute(["slug" => $slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$page) {
        json_error("Página no encontrada", 404);
    }

    json_success([
        "data" => [
            "id" => (int)$page["id"],
            "slug" => $page["slug"],
            "title" => $page["title"],
            "meta_description" => $page["meta_description"],
            "content" => $page["content"],
            "created_at" => $page["created_at"],
            "updated_at" => $page["updated_at"],
        ],
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener página");
}
