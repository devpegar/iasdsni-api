<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

$user = require_role(["admin"]);

$id = intval($_GET["id"] ?? 0);

if (!$id) {
    echo json_encode(["success" => false, "message" => "ID inválido"]);
    exit;
}

// Evitar que un admin se elimine a sí mismo
if ($id == $user["id"]) {
    echo json_encode([
        "success" => false,
        "message" => "No puedes eliminar tu propio usuario"
    ]);
    exit;
}

// Verificar existencia
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    echo json_encode([
        "success" => false,
        "message" => "El usuario no existe"
    ]);
    exit;
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

    echo json_encode([
        "success" => true,
        "message" => "Usuario eliminado correctamente"
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        "success" => false,
        "message" => "Error al eliminar usuario",
        "error" => $e->getMessage()
    ]);
}
