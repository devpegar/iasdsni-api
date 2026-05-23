<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("GET");
require_role(["admin"]);

try {
    $stmt = $pdo->query("
        SELECT
            a.id, a.title, a.slug, a.description, a.cover_media_id, a.event_date,
            a.sort_order, a.is_featured, a.is_active, a.created_at, a.updated_at,
            cover.public_url AS cover_image_url,
            cover.alt_text AS cover_alt_text,
            (
                SELECT COUNT(*)
                FROM gallery_album_items i
                WHERE i.album_id = a.id AND i.is_active = 1
            ) AS total_items
        FROM gallery_albums a
        LEFT JOIN media_files cover ON cover.id = a.cover_media_id
        ORDER BY a.sort_order ASC, a.event_date DESC, a.id DESC
    ");

    $albums = array_map(function ($album) {
        return [
            "id" => (int)$album["id"],
            "title" => $album["title"],
            "slug" => $album["slug"],
            "description" => $album["description"] ?? "",
            "cover_media_id" => $album["cover_media_id"] ? (int)$album["cover_media_id"] : null,
            "cover_image_url" => $album["cover_image_url"],
            "cover_alt_text" => $album["cover_alt_text"] ?? "",
            "event_date" => $album["event_date"],
            "sort_order" => (int)$album["sort_order"],
            "is_featured" => (int)$album["is_featured"],
            "is_active" => (int)$album["is_active"],
            "total_items" => (int)$album["total_items"],
            "created_at" => $album["created_at"],
            "updated_at" => $album["updated_at"],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success(["albums" => $albums]);
} catch (PDOException $e) {
    if ($e->getCode() === "42S02") {
        json_success(["albums" => []]);
    }

    error_log($e->getMessage());
    json_error("Error al obtener álbumes");
}
