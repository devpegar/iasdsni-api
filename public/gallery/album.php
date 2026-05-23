<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";

require_method("GET");

$slug = trim($_GET["slug"] ?? "");

if ($slug === "") {
    json_error("Debe indicar un álbum", 400);
}

try {
    $stmt = $pdo->prepare("
        SELECT id, title, slug, description, event_date, is_featured
        FROM gallery_albums
        WHERE slug = :slug AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute(["slug" => $slug]);
    $album = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$album) {
        json_error("Álbum no encontrado", 404);
    }

    $stmt = $pdo->prepare("
        SELECT i.id, i.media_file_id, m.public_url, m.alt_text, i.caption, i.sort_order
        FROM gallery_album_items i
        INNER JOIN media_files m ON m.id = i.media_file_id
        WHERE i.album_id = :album_id
          AND i.is_active = 1
          AND m.is_active = 1
        ORDER BY i.sort_order ASC, i.id ASC
    ");
    $stmt->execute(["album_id" => $album["id"]]);

    $items = array_map(function ($item) {
        return [
            "id" => (int)$item["id"],
            "media_file_id" => (int)$item["media_file_id"],
            "public_url" => $item["public_url"],
            "alt_text" => $item["alt_text"] ?? "",
            "caption" => $item["caption"] ?? "",
            "sort_order" => (int)$item["sort_order"],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success([
        "data" => [
            "id" => (int)$album["id"],
            "title" => $album["title"],
            "slug" => $album["slug"],
            "description" => $album["description"],
            "event_date" => $album["event_date"],
            "is_featured" => (int)$album["is_featured"],
            "items" => $items,
        ],
    ]);
} catch (PDOException $e) {
    if (in_array($e->getCode(), ["42S02", "42S22"], true)) {
        json_error("Álbum no encontrado", 404);
    }

    error_log($e->getMessage());
    json_error("Error al obtener álbum");
}
