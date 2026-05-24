<?php

require_once "../../utils/cors.php";
require_once "../../utils/http.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

$user = require_role(["admin"]);

$data = read_json_body();
$id = intval($data["id"] ?? $_GET["id"] ?? 0);

if (!$id) {
    json_error("ID inválido", 422);
}

// Evitar que un admin se elimine a sí mismo
if ($id == $user["id"]) {
    json_error("No puedes eliminar tu propio usuario", 409);
}

// Verificar existencia
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json_error("El usuario no existe", 404);
}

try {
    $pdo->beginTransaction();

    // Eliminar departamentos asignados
    $pdo->prepare("DELETE FROM user_departments WHERE user_id = ?")
        ->execute([$id]);

    // Eliminar usuario
    $pdo->prepare("DELETE FROM users WHERE id = ?")
        ->execute([$id]);

    $pdo->commit();

    json_success(["id" => $id], "Usuario eliminado correctamente");
} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    json_error("Error al eliminar usuario");
}
