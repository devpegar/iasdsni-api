<?php

require_once "../../utils/cors.php";
require_once "../../utils/http.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$data = read_json_body();
$id = intval($data["id"] ?? $_GET["id"] ?? 0);

if (!$id) {
    json_error("ID inválido", 422);
}

$stmt = $pdo->prepare("SELECT id FROM daily_verses WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    json_error("El verso no existe", 404);
}

try {
    $stmt = $pdo->prepare("DELETE FROM daily_verses WHERE id = ?");
    $stmt->execute([$id]);

    json_success(["id" => $id], "Verso eliminado correctamente");
} catch (Exception $e) {
    error_log($e->getMessage());
    json_error("Error al eliminar verso");
}
