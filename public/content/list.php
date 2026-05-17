<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";

require_method("GET");

$allowedTypes = ["news", "announcement"];
$type = trim($_GET["type"] ?? "");
$limit = (int)($_GET["limit"] ?? 6);
$limit = max(1, min($limit, 50));

if (!in_array($type, $allowedTypes, true)) {
    json_error("Tipo de contenido inválido", 400);
}

try {
    $stmt = $pdo->prepare("
        SELECT id, slug, title, excerpt, featured_image, published_at, updated_at
        FROM pages
        WHERE page_type = :type
          AND is_active = 1
          AND (published_at IS NULL OR published_at <= NOW())
        ORDER BY published_at DESC, updated_at DESC
        LIMIT {$limit}
    ");

    $stmt->execute(["type" => $type]);

    $items = array_map(function ($item) {
        return [
            "id" => (int)$item["id"],
            "slug" => $item["slug"],
            "title" => $item["title"],
            "excerpt" => $item["excerpt"],
            "featured_image" => $item["featured_image"],
            "published_at" => $item["published_at"],
            "updated_at" => $item["updated_at"],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success(["data" => $items]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener contenido");
}
