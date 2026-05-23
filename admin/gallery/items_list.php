<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("GET");
require_role(["admin"]);

$albumId = (int)($_GET["album_id"] ?? 0);

if (!$albumId) {
    json_error("Álbum inválido", 422);
}

try {
    $stmt = $pdo->prepare("
        SELECT i.id, i.album_id, i.media_file_id, i.caption, i.sort_order, i.is_active, i.created_at,
               m.public_url, m.alt_text, m.original_name
        FROM gallery_album_items i
        INNER JOIN media_files m ON m.id = i.media_file_id
        WHERE i.album_id = :album_id AND i.is_active = 1
        ORDER BY i.sort_order ASC, i.id ASC
    ");
    $stmt->execute(["album_id" => $albumId]);

    $items = array_map(function ($item) {
        return [
            "id" => (int)$item["id"],
            "album_id" => (int)$item["album_id"],
            "media_file_id" => (int)$item["media_file_id"],
            "caption" => $item["caption"] ?? "",
            "sort_order" => (int)$item["sort_order"],
            "is_active" => (int)$item["is_active"],
            "public_url" => $item["public_url"],
            "alt_text" => $item["alt_text"] ?? "",
            "original_name" => $item["original_name"],
            "created_at" => $item["created_at"],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success(["items" => $items]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener imágenes del álbum");
}
