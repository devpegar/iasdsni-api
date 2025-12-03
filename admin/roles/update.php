<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data["id"] ?? 0);
$name = trim($data["name"] ?? "");
$description = trim($data["description"] ?? "");

if (!$id || !$name) {
    echo json_encode([
        "success" => false,
        "message" => "ID y nombre son obligatorios"
    ]);
    exit;
}

// Verificar que el rol exista
$stmt = $pdo->prepare("SELECT id FROM roles WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    echo json_encode(["success" => false, "message" => "El rol no existe"]);
    exit;
}

// Verificar duplicado de nombre
$stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ? AND id != ?");
$stmt->execute([$name, $id]);
if ($stmt->fetch()) {
    echo json_encode([
        "success" => false,
        "message" => "Ya existe un rol con ese nombre"
    ]);
    exit;
}

// Actualizar
$stmt = $pdo->prepare("
    UPDATE roles 
    SET name = ?, description = ?
    WHERE id = ?
");

$success = $stmt->execute([$name, $description, $id]);

echo json_encode([
    "success" => $success,
    "message" => $success ? "Rol actualizado" : "Error al actualizar rol"
]);
