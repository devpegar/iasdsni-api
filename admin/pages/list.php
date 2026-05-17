<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("GET");
require_role(["admin"]);

$q = trim($_GET["q"] ?? "");

try {
    if ($q !== "") {
        $stmt = $pdo->prepare("
            SELECT id, slug, title, page_type, meta_description, excerpt, featured_image, content,
                   published_at, is_active, created_at, updated_at
            FROM pages
            WHERE slug LIKE :q OR title LIKE :q
            ORDER BY updated_at DESC
        ");
        $stmt->execute(["q" => "%{$q}%"]);
    } else {
        $stmt = $pdo->query("
            SELECT id, slug, title, page_type, meta_description, excerpt, featured_image, content,
                   published_at, is_active, created_at, updated_at
            FROM pages
            ORDER BY updated_at DESC
        ");
    }

    $pages = array_map(function ($page) {
        return [
            "id" => (int)$page["id"],
            "slug" => $page["slug"],
            "title" => $page["title"],
            "page_type" => $page["page_type"],
            "meta_description" => $page["meta_description"],
            "excerpt" => $page["excerpt"],
            "featured_image" => $page["featured_image"],
            "content" => $page["content"],
            "published_at" => $page["published_at"],
            "is_active" => (int)$page["is_active"],
            "created_at" => $page["created_at"],
            "updated_at" => $page["updated_at"],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success(["pages" => $pages]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener páginas");
}
