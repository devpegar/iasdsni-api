<?php

require_once "../utils/cors.php";
require_once "../config/database.php";

header("Content-Type: application/json");

$user = require_auth();

if ($user["role"] !== "admin") {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tienes permisos"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data["email"] ?? "");
$username = trim($data["username"] ?? "");
$password = trim($data["password"] ?? "");
$role = "admin"; // o "editor", según cómo quieras manejarlo

// Validación
if (!$email || !$username || !$password) {
    echo json_encode([
        "success" => false,
        "message" => "Todos los campos son obligatorios"
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "El email no es válido"
    ]);
    exit;
}

// ¿Email duplicado?
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    echo json_encode([
        "success" => false,
        "message" => "El email ya está registrado"
    ]);
    exit;
}

// ¿Username duplicado?
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);

if ($stmt->rowCount() > 0) {
    echo json_encode([
        "success" => false,
        "message" => "El nombre de usuario ya está en uso"
    ]);
    exit;
}

// Insertar el usuario
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users (email, username, password, role)
    VALUES (?, ?, ?, ?)
");

$success = $stmt->execute([
    $email,
    $username,
    $hashedPassword,
    $role
]);

if (!$success) {
    echo json_encode([
        "success" => false,
        "message" => "Error al registrar usuario"
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "message" => "Usuario registrado correctamente"
]);
