<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";

require_method("GET");

$featured = isset($_GET["featured"]) && (int)$_GET["featured"] === 1;
$limit = isset($_GET["limit"]) && is_numeric($_GET["limit"]) ? max(1, min(50, (int)$_GET["limit"])) : null;

try {
    $where = ["a.is_active = 1"];

    if ($featured) {
        $where[] = "a.is_featured = 1";
    }

    $sql = "
        SELECT
            a.id, a.title, a.slug, a.description, a.event_date, a.is_featured,
            COALESCE(cover.public_url, first_item.public_url) AS cover_image_url,
            COALESCE(cover.alt_text, first_item.alt_text) AS cover_alt_text,
            (
                SELECT COUNT(*)
                FROM gallery_album_items count_items
                INNER JOIN media_files count_media ON count_media.id = count_items.media_file_id
                WHERE count_items.album_id = a.id
                  AND count_items.is_active = 1
                  AND count_media.is_active = 1
            ) AS total_items
        FROM gallery_albums a
        LEFT JOIN media_files cover ON cover.id = a.cover_media_id AND cover.is_active = 1
        LEFT JOIN gallery_album_items first_link ON first_link.id = (
            SELECT i.id
            FROM gallery_album_items i
            INNER JOIN media_files m ON m.id = i.media_file_id
            WHERE i.album_id = a.id
              AND i.is_active = 1
              AND m.is_active = 1
            ORDER BY i.sort_order ASC, i.id ASC
            LIMIT 1
        )
        LEFT JOIN media_files first_item ON first_item.id = first_link.media_file_id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY a.sort_order ASC, a.event_date DESC, a.id DESC
    ";

    if ($limit !== null) {
        $sql .= " LIMIT " . $limit;
    }

    $stmt = $pdo->query($sql);
    $albums = array_map(function ($album) {
        return [
            "id" => (int)$album["id"],
            "title" => $album["title"],
            "slug" => $album["slug"],
            "description" => $album["description"],
            "event_date" => $album["event_date"],
            "is_featured" => (int)$album["is_featured"],
            "cover_image_url" => $album["cover_image_url"],
            "cover_alt_text" => $album["cover_alt_text"] ?? "",
            "total_items" => (int)$album["total_items"],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    json_success(["data" => $albums]);
} catch (PDOException $e) {
    if (in_array($e->getCode(), ["42S02", "42S22"], true)) {
        json_success(["data" => []]);
    }

    error_log($e->getMessage());
    json_error("Error al obtener álbumes");
}
