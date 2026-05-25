<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";

require_method("GET");

$slug = trim($_GET["slug"] ?? "");

if ($slug === "") {
    json_error("Debe indicar una doctrina", 400);
}

try {
    $stmt = $pdo->prepare("
        SELECT id, title, slug, summary, image_url, sort_order, is_active, created_at, updated_at
        FROM belief_doctrines
        WHERE slug = :slug AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute(["slug" => $slug]);
    $doctrine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctrine) {
        json_error("Doctrina no encontrada", 404);
    }

    $stmt = $pdo->prepare("
        SELECT id, doctrine_id, title, slug, content, bible_references, sort_order, is_active, created_at, updated_at
        FROM belief_items
        WHERE doctrine_id = :doctrine_id AND is_active = 1
        ORDER BY sort_order ASC, title ASC
    ");
    $stmt->execute(["doctrine_id" => (int)$doctrine["id"]]);

    $items = array_map(function ($row) {
        return [
            "id" => (int)$row["id"],
            "doctrine_id" => (int)$row["doctrine_id"],
            "title" => $row["title"],
            "slug" => $row["slug"],
            "content" => $row["content"],
            "bible_references" => $row["bible_references"],
            "sort_order" => (int)$row["sort_order"],
            "created_at" => $row["created_at"],
            "updated_at" => $row["updated_at"],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success([
        "data" => [
            "doctrine" => [
                "id" => (int)$doctrine["id"],
                "title" => $doctrine["title"],
                "slug" => $doctrine["slug"],
                "summary" => $doctrine["summary"],
                "image_url" => $doctrine["image_url"],
                "sort_order" => (int)$doctrine["sort_order"],
                "created_at" => $doctrine["created_at"],
                "updated_at" => $doctrine["updated_at"],
            ],
            "items" => $items,
        ],
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener doctrina");
}
