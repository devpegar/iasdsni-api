<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("POST");
require_role(["admin"]);

$data = read_json_body();
$id = (int)($data["id"] ?? 0);

if (!$id) {
    json_error("ID inválido", 422);
}

$altText = array_key_exists("alt_text", $data)
    ? trim((string)$data["alt_text"])
    : "";

try {
    $stmt = $pdo->prepare("
        SELECT id
        FROM media_files
        WHERE id = :id AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute(["id" => $id]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        json_error("Archivo no encontrado", 404);
    }

    $stmt = $pdo->prepare("
        UPDATE media_files
        SET alt_text = :alt_text
        WHERE id = :id
    ");
    $stmt->execute([
        "alt_text" => $altText !== "" ? $altText : null,
        "id" => $id,
    ]);

    json_success(["id" => $id, "alt_text" => $altText], "Texto alternativo actualizado");
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al actualizar archivo multimedia");
}
