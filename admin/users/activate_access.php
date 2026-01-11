<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo admin
require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$user_id  = intval($data["id"] ?? 0);
$email    = trim($data["email"] ?? "");
$password = trim($data["password"] ?? "");

// =====================
// Validaciones básicas
// =====================
if (!$user_id || !$email || !$password) {
    echo json_encode([
        "success" => false,
        "message" => "ID, email y contraseña son obligatorios"
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Email inválido"
    ]);
    exit;
}

// =====================
// Verificar usuario
// =====================
$stmt = $pdo->prepare("
    SELECT id, has_access
    FROM users
    WHERE id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "Usuario no encontrado"
    ]);
    exit;
}

if ((int)$user["has_access"] === 1) {
    echo json_encode([
        "success" => false,
        "message" => "El usuario ya tiene acceso al sistema"
    ]);
    exit;
}

// =====================
// Email único
// =====================
$stmt = $pdo->prepare("
    SELECT id FROM users
    WHERE email = ? AND id != ?
");
$stmt->execute([$email, $user_id]);

if ($stmt->fetch()) {
    echo json_encode([
        "success" => false,
        "message" => "El email ya está en uso por otro usuario"
    ]);
    exit;
}

// =====================
// Activar acceso
// =====================
$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    UPDATE users
    SET
        email = ?,
        password = ?,
        has_access = 1
    WHERE id = ?
");

$stmt->execute([$email, $hashed, $user_id]);

echo json_encode([
    "success" => true,
    "message" => "Acceso al sistema activado correctamente"
]);
