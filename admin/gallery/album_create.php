<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";
require_once __DIR__ . "/_helpers.php";

require_method("POST");
require_role(["admin"]);

$data = read_json_body();
$title = trim((string)($data["title"] ?? ""));
$rawSlug = trim((string)($data["slug"] ?? ""));
$slug = normalize_gallery_slug($rawSlug !== "" ? $rawSlug : $title);

if ($title === "" || $slug === "") {
    json_error("Título y slug son obligatorios", 422);
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO gallery_albums (
            title, slug, description, cover_media_id, event_date, sort_order, is_featured, is_active
        )
        VALUES (
            :title, :slug, :description, :cover_media_id, :event_date, :sort_order, :is_featured, :is_active
        )
    ");
    $stmt->execute([
        "title" => $title,
        "slug" => $slug,
        "description" => nullable_trim($data["description"] ?? ""),
        "cover_media_id" => !empty($data["cover_media_id"]) ? (int)$data["cover_media_id"] : null,
        "event_date" => normalize_date_or_null($data["event_date"] ?? ""),
        "sort_order" => is_numeric($data["sort_order"] ?? null) ? (int)$data["sort_order"] : 0,
        "is_featured" => !empty($data["is_featured"]) ? 1 : 0,
        "is_active" => array_key_exists("is_active", $data) ? ((int)$data["is_active"] ? 1 : 0) : 1,
    ]);

    json_success(["id" => (int)$pdo->lastInsertId(), "slug" => $slug], "Álbum creado correctamente", 201);
} catch (PDOException $e) {
    if ($e->getCode() === "23000") {
        json_error("Ya existe un álbum con ese slug", 409);
    }

    error_log($e->getMessage());
    json_error("Error al crear álbum");
}
