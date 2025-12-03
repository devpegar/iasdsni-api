<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo admin puede crear usuarios
$admin = require_role(["admin"]);

// Recibir datos
$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data["email"] ?? "");
$username = trim($data["username"] ?? "");
$password = trim($data["password"] ?? "");
$roleId = intval($data["role_id"] ?? 0); // ahora se usa role_id
$departments = $data["departments"] ?? []; // array de IDs

// Validación
if (!$email || !$username || !$password || !$roleId) {
    echo json_encode([
        "success" => false,
        "message" => "Todos los campos obligatorios deben ser completados"
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

// Validar rol por ID
$stmt = $pdo->prepare("SELECT id FROM roles WHERE id = ?");
$stmt->execute([$roleId]);
if (!$stmt->fetch()) {
    echo json_encode([
        "success" => false,
        "message" => "El rol indicado no existe"
    ]);
    exit;
}

// Validar duplicados email
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    echo json_encode([
        "success" => false,
        "message" => "El email ya está registrado"
    ]);
    exit;
}

// Validar duplicados username
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->rowCount() > 0) {
    echo json_encode([
        "success" => false,
        "message" => "El nombre de usuario ya existe"
    ]);
    exit;
}

// Validar departamentos existentes
if (!empty($departments)) {

    // Asegurar que todos sean números
    $departments = array_map("intval", $departments);

    $placeholders = implode(",", array_fill(0, count($departments), "?"));

    $stmt = $pdo->prepare("SELECT id FROM departments WHERE id IN ($placeholders)");
    $stmt->execute($departments);

    if ($stmt->rowCount() !== count($departments)) {
        echo json_encode([
            "success" => false,
            "message" => "Uno o más departamentos no existen"
        ]);
        exit;
    }
}

// Hashear contraseña
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    // Insertar usuario
    $stmt = $pdo->prepare("
        INSERT INTO users (email, username, password, role_id)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([$email, $username, $hashedPassword, $roleId]);

    $userId = $pdo->lastInsertId();

    // Insertar departamentos
    if (!empty($departments)) {
        $stmtDep = $pdo->prepare("
            INSERT INTO user_departments (user_id, department_id)
            VALUES (?, ?)
        ");

        foreach ($departments as $depId) {
            $stmtDep->execute([$userId, $depId]);
        }
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Usuario creado correctamente"
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        "success" => false,
        "message" => "Error al crear el usuario",
        "error" => $e->getMessage()
    ]);
}
