<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";
require_once __DIR__ . "/_helpers.php";

require_method("POST");
require_role(["admin"]);

$data = read_json_body();
$albumId = (int)($data["album_id"] ?? 0);
$mediaFileId = (int)($data["media_file_id"] ?? 0);

if (!$albumId || !$mediaFileId) {
    json_error("Álbum e imagen son obligatorios", 422);
}

try {
    $stmt = $pdo->prepare("
        SELECT id
        FROM gallery_album_items
        WHERE album_id = :album_id AND media_file_id = :media_file_id AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute(["album_id" => $albumId, "media_file_id" => $mediaFileId]);

    if ($stmt->fetch()) {
        json_error("La imagen ya está en el álbum", 409);
    }

    $stmt = $pdo->prepare("
        INSERT INTO gallery_album_items (album_id, media_file_id, caption, sort_order, is_active)
        VALUES (:album_id, :media_file_id, :caption, :sort_order, 1)
    ");
    $stmt->execute([
        "album_id" => $albumId,
        "media_file_id" => $mediaFileId,
        "caption" => nullable_trim($data["caption"] ?? ""),
        "sort_order" => is_numeric($data["sort_order"] ?? null) ? (int)$data["sort_order"] : 0,
    ]);

    json_success(["id" => (int)$pdo->lastInsertId()], "Imagen agregada correctamente", 201);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al agregar imagen");
}
