<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo admin
$admin = require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data["id"] ?? 0);

if (!$id) {
    echo json_encode([
        "success" => false,
        "message" => "ID inválido"
    ]);
    exit;
}

// Verificar que existe
$stmt = $pdo->prepare("SELECT id FROM departments WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    echo json_encode([
        "success" => false,
        "message" => "El departamento no existe"
    ]);
    exit;
}

// Evitar borrar si está asignado a usuarios
$stmt = $pdo->prepare("
    SELECT user_id FROM user_departments WHERE department_id = ?
");
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    echo json_encode([
        "success" => false,
        "message" => "No puede eliminarse: hay usuarios asignados"
    ]);
    exit;
}

// Borrar
$stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
$stmt->execute([$id]);

echo json_encode([
    "success" => true,
    "message" => "Departamento eliminado correctamente"
]);
