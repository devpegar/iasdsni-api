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

try {
    $stmt = $pdo->prepare("UPDATE gallery_albums SET is_active = 0 WHERE id = :id");
    $stmt->execute(["id" => $id]);
    json_success(["id" => $id], "Álbum desactivado correctamente");
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al desactivar álbum");
}
