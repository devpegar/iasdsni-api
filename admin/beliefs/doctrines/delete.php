<?php

require_once __DIR__ . "/../../../utils/cors.php";
require_once __DIR__ . "/../../../utils/http.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../middleware/auth.php";

require_method("POST");
require_role(["admin"]);

$data = read_json_body();
$id = (int)($data["id"] ?? 0);

if (!$id) {
    json_error("ID inválido", 422);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM belief_doctrines WHERE id = :id LIMIT 1");
    $stmt->execute(["id" => $id]);

    if (!$stmt->fetch()) {
        json_error("La doctrina no existe", 404);
    }

    $stmt = $pdo->prepare("UPDATE belief_doctrines SET is_active = 0 WHERE id = :id");
    $stmt->execute(["id" => $id]);

    json_success(["id" => $id, "is_active" => 0], "Doctrina desactivada correctamente");
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al desactivar doctrina");
}
