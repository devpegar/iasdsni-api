<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo admin
$admin = require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data["id"] ?? 0);
$name = trim($data["name"] ?? "");
$description = trim($data["description"] ?? "");

// Validaciones
if (!$id || !$name) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan datos obligatorios"
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

// Verificar duplicado de nombre (excepto este mismo id)
$stmt = $pdo->prepare("
    SELECT id FROM departments WHERE name = ? AND id != ?
");
$stmt->execute([$name, $id]);

if ($stmt->rowCount() > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Ya existe otro departamento con ese nombre"
    ]);
    exit;
}

// Actualizar
$stmt = $pdo->prepare("
    UPDATE departments
    SET name = ?, description = ?
    WHERE id = ?
");

$stmt->execute([$name, $description, $id]);

echo json_encode([
    "success" => true,
    "message" => "Departamento actualizado correctamente"
]);
