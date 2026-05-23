<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";
require_once __DIR__ . "/_helpers.php";

require_method("POST");
require_role(["admin"]);

$data = read_json_body();
$id = (int)($data["id"] ?? 0);

if (!$id) {
    json_error("ID inválido", 422);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM gallery_albums WHERE id = :id LIMIT 1");
    $stmt->execute(["id" => $id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current) {
        json_error("Álbum no encontrado", 404);
    }

    $title = array_key_exists("title", $data) ? trim((string)$data["title"]) : $current["title"];
    $rawSlug = array_key_exists("slug", $data) ? trim((string)$data["slug"]) : $current["slug"];
    $slug = normalize_gallery_slug($rawSlug !== "" ? $rawSlug : $title);

    if ($title === "" || $slug === "") {
        json_error("Título y slug son obligatorios", 422);
    }

    $stmt = $pdo->prepare("SELECT id FROM gallery_albums WHERE slug = :slug AND id <> :id LIMIT 1");
    $stmt->execute(["slug" => $slug, "id" => $id]);
    if ($stmt->fetch()) {
        json_error("Ya existe un álbum con ese slug", 409);
    }

    $stmt = $pdo->prepare("
        UPDATE gallery_albums
        SET title = :title,
            slug = :slug,
            description = :description,
            cover_media_id = :cover_media_id,
            event_date = :event_date,
            sort_order = :sort_order,
            is_featured = :is_featured,
            is_active = :is_active
        WHERE id = :id
    ");
    $stmt->execute([
        "title" => $title,
        "slug" => $slug,
        "description" => array_key_exists("description", $data) ? nullable_trim($data["description"]) : $current["description"],
        "cover_media_id" => array_key_exists("cover_media_id", $data)
            ? (!empty($data["cover_media_id"]) ? (int)$data["cover_media_id"] : null)
            : $current["cover_media_id"],
        "event_date" => array_key_exists("event_date", $data) ? normalize_date_or_null($data["event_date"]) : $current["event_date"],
        "sort_order" => array_key_exists("sort_order", $data) && is_numeric($data["sort_order"]) ? (int)$data["sort_order"] : (int)$current["sort_order"],
        "is_featured" => array_key_exists("is_featured", $data) ? ((int)$data["is_featured"] ? 1 : 0) : (int)$current["is_featured"],
        "is_active" => array_key_exists("is_active", $data) ? ((int)$data["is_active"] ? 1 : 0) : (int)$current["is_active"],
        "id" => $id,
    ]);

    json_success(["id" => $id, "slug" => $slug], "Álbum actualizado correctamente");
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al actualizar álbum");
}
