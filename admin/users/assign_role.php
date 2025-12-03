<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo admin puede asignar roles
require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$userId = intval($data["user_id"] ?? 0);
$roleId = intval($data["role_id"] ?? 0);

if (!$userId || !$roleId) {
    echo json_encode([
        "success" => false,
        "message" => "user_id y role_id son obligatorios"
    ]);
    exit;
}

// Verificar usuario
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$userId]);

if ($stmt->rowCount() === 0) {
    echo json_encode([
        "success" => false,
        "message" => "El usuario no existe"
    ]);
    exit;
}

// Verificar rol
$stmt = $pdo->prepare("SELECT id FROM roles WHERE id = ?");
$stmt->execute([$roleId]);

if ($stmt->rowCount() === 0) {
    echo json_encode([
        "success" => false,
        "message" => "El rol no existe"
    ]);
    exit;
}

// Asignar rol
$stmt = $pdo->prepare("
    UPDATE users SET role_id = ? WHERE id = ?
");

$success = $stmt->execute([$roleId, $userId]);

echo json_encode([
    "success" => $success,
    "message" => $success ? "Rol asignado correctamente" : "Error al asignar el rol"
]);
