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

function pages_have_seo_columns($pdo)
{
    $stmt = $pdo->query("SHOW COLUMNS FROM pages LIKE 'seo_title'");
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

require_method("POST");
require_role(["admin"]);

$data = read_json_body();
$id = (int)($data["id"] ?? 0);

if (!$id) {
    json_error("ID inválido", 422);
}

try {
    $hasSeoColumns = pages_have_seo_columns($pdo);
    $seoSelect = $hasSeoColumns ? ", seo_title, og_image, canonical_url, noindex" : "";

    $stmt = $pdo->prepare("
        SELECT id, slug, title, page_type, meta_description, excerpt, featured_image, content, published_at, is_active{$seoSelect}
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
    $pageType = array_key_exists("page_type", $data)
        ? trim((string)$data["page_type"])
        : $currentPage["page_type"];
    $metaDescription = array_key_exists("meta_description", $data)
        ? trim((string)$data["meta_description"])
        : $currentPage["meta_description"];
    $excerpt = array_key_exists("excerpt", $data)
        ? trim((string)$data["excerpt"])
        : $currentPage["excerpt"];
    $featuredImage = array_key_exists("featured_image", $data)
        ? trim((string)$data["featured_image"])
        : $currentPage["featured_image"];
    $seoTitle = array_key_exists("seo_title", $data)
        ? trim((string)$data["seo_title"])
        : ($currentPage["seo_title"] ?? "");
    $ogImage = array_key_exists("og_image", $data)
        ? trim((string)$data["og_image"])
        : ($currentPage["og_image"] ?? "");
    $canonicalUrl = array_key_exists("canonical_url", $data)
        ? trim((string)$data["canonical_url"])
        : ($currentPage["canonical_url"] ?? "");
    $noindex = array_key_exists("noindex", $data)
        ? ((int)$data["noindex"] ? 1 : 0)
        : (int)($currentPage["noindex"] ?? 0);
    $content = array_key_exists("content", $data)
        ? ($data["content"] !== null ? (string)$data["content"] : null)
        : $currentPage["content"];
    $publishedAt = array_key_exists("published_at", $data)
        ? trim((string)$data["published_at"])
        : $currentPage["published_at"];
    $isActive = array_key_exists("is_active", $data)
        ? ((int)$data["is_active"] ? 1 : 0)
        : (int)$currentPage["is_active"];
    $allowedPageTypes = ["page", "news", "announcement", "event"];

    if ($slug === "" || $title === "") {
        json_error("Slug y título son obligatorios", 422);
    }

    if (!in_array($pageType, $allowedPageTypes, true)) {
        json_error("Tipo de contenido inválido", 422);
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

    $seoUpdate = $hasSeoColumns ? ",
            seo_title = :seo_title,
            og_image = :og_image,
            canonical_url = :canonical_url,
            noindex = :noindex" : "";

    $stmt = $pdo->prepare("
        UPDATE pages
        SET slug = :slug,
            title = :title,
            page_type = :page_type,
            meta_description = :meta_description,
            excerpt = :excerpt,
            featured_image = :featured_image,
            content = :content,
            published_at = :published_at,
            is_active = :is_active{$seoUpdate}
        WHERE id = :id
    ");

    $params = [
        "slug" => $slug,
        "title" => $title,
        "page_type" => $pageType,
        "meta_description" => $metaDescription !== "" ? $metaDescription : null,
        "excerpt" => $excerpt !== "" ? $excerpt : null,
        "featured_image" => $featuredImage !== "" ? $featuredImage : null,
        "content" => $content,
        "published_at" => $publishedAt !== "" ? $publishedAt : null,
        "is_active" => $isActive,
        "id" => $id,
    ];

    if ($hasSeoColumns) {
        $params["seo_title"] = $seoTitle !== "" ? $seoTitle : null;
        $params["og_image"] = $ogImage !== "" ? $ogImage : null;
        $params["canonical_url"] = $canonicalUrl !== "" ? $canonicalUrl : null;
        $params["noindex"] = $noindex;
    }

    $stmt->execute($params);

    json_success([
        "id" => $id,
        "slug" => $slug,
        "is_active" => $isActive,
    ], "Página actualizada correctamente");
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al actualizar página");
}
