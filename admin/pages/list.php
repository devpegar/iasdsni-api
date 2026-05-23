<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("GET");
require_role(["admin"]);

$q = trim($_GET["q"] ?? "");

function pages_have_seo_columns($pdo)
{
    $stmt = $pdo->query("SHOW COLUMNS FROM pages LIKE 'seo_title'");
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

try {
    $hasSeoColumns = pages_have_seo_columns($pdo);
    $seoSelect = $hasSeoColumns ? ", seo_title, og_image, canonical_url, noindex" : "";

    if ($q !== "") {
        $stmt = $pdo->prepare("
            SELECT id, slug, title, page_type, meta_description, excerpt, featured_image, content,
                   published_at, is_active, created_at, updated_at{$seoSelect}
            FROM pages
            WHERE slug LIKE :q OR title LIKE :q
            ORDER BY updated_at DESC
        ");
        $stmt->execute(["q" => "%{$q}%"]);
    } else {
        $stmt = $pdo->query("
            SELECT id, slug, title, page_type, meta_description, excerpt, featured_image, content,
                   published_at, is_active, created_at, updated_at{$seoSelect}
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
            "seo_title" => $page["seo_title"] ?? "",
            "og_image" => $page["og_image"] ?? "",
            "canonical_url" => $page["canonical_url"] ?? "",
            "noindex" => (int)($page["noindex"] ?? 0),
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success(["pages" => $pages]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener páginas");
}
