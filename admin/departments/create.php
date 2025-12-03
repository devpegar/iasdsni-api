<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo admin puede crear
$admin = require_role(["admin"]);

// Recibir datos
$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data["name"] ?? "");
$description = trim($data["description"] ?? "");

// Validación básica
if (!$name) {
    echo json_encode([
        "success" => false,
        "message" => "El nombre del departamento es obligatorio"
    ]);
    exit;
}

// Validar duplicado
$stmt = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
$stmt->execute([$name]);

if ($stmt->rowCount() > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Ya existe un departamento con ese nombre"
    ]);
    exit;
}

// Crear departamento
$stmt = $pdo->prepare("
    INSERT INTO departments (name, description)
    VALUES (?, ?)
");

$stmt->execute([$name, $description]);

echo json_encode([
    "success" => true,
    "message" => "Departamento creado correctamente"
]);
