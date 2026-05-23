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
    $stmt = $pdo->prepare("SELECT * FROM gallery_album_items WHERE id = :id LIMIT 1");
    $stmt->execute(["id" => $id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current) {
        json_error("Imagen no encontrada", 404);
    }

    $stmt = $pdo->prepare("
        UPDATE gallery_album_items
        SET caption = :caption,
            sort_order = :sort_order,
            is_active = :is_active
        WHERE id = :id
    ");
    $stmt->execute([
        "caption" => array_key_exists("caption", $data) ? nullable_trim($data["caption"]) : $current["caption"],
        "sort_order" => array_key_exists("sort_order", $data) && is_numeric($data["sort_order"]) ? (int)$data["sort_order"] : (int)$current["sort_order"],
        "is_active" => array_key_exists("is_active", $data) ? ((int)$data["is_active"] ? 1 : 0) : (int)$current["is_active"],
        "id" => $id,
    ]);

    json_success(["id" => $id], "Imagen actualizada correctamente");
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al actualizar imagen");
}
