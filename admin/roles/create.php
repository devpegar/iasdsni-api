<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo admin puede crear roles
require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data["name"] ?? "");
$description = trim($data["description"] ?? "");

if (!$name) {
    echo json_encode([
        "success" => false,
        "message" => "El nombre del rol es obligatorio"
    ]);
    exit;
}

// Verificar duplicado
$stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
$stmt->execute([$name]);

if ($stmt->rowCount() > 0) {
    echo json_encode([
        "success" => false,
        "message" => "El rol ya existe"
    ]);
    exit;
}

// Insertar nuevo rol
$stmt = $pdo->prepare("
    INSERT INTO roles (name, description)
    VALUES (?, ?)
");

$success = $stmt->execute([$name, $description]);

echo json_encode([
    "success" => $success,
    "message" => $success ? "Rol creado correctamente" : "Error al crear el rol"
]);
