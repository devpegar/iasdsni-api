<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo secretaria puede asignar departamentos
require_role("secretaria");

$data = json_decode(file_get_contents("php://input"), true);

$userId = intval($data["user_id"] ?? 0);
$departmentId = intval($data["department_id"] ?? 0);

if (!$userId || !$departmentId) {
    echo json_encode([
        "success" => false,
        "message" => "user_id y department_id son obligatorios"
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

// Verificar departamento
$stmt = $pdo->prepare("SELECT id FROM departments WHERE id = ?");
$stmt->execute([$departmentId]);

if ($stmt->rowCount() === 0) {
    echo json_encode([
        "success" => false,
        "message" => "El departamento no existe"
    ]);
    exit;
}

// Registrar asignación
$stmt = $pdo->prepare("
    INSERT INTO user_departments (user_id, department_id)
    VALUES (?, ?)
");

$success = $stmt->execute([$userId, $departmentId]);

echo json_encode([
    "success" => $success,
    "message" => $success ? "Departamento asignado correctamente" : "Error al asignar departamento"
]);
