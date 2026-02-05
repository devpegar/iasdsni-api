<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$username    = trim($data["username"] ?? "");
$email       = trim($data["email"] ?? "");
$password    = trim($data["password"] ?? "");
$roleId      = intval($data["role_id"] ?? 0);
$departments = $data["departments"] ?? [];
$has_access  = intval($data["has_access"] ?? 0);
$active      = isset($data["active"]) ? intval($data["active"]) : 1;

if (!$username || !$roleId) {
    echo json_encode(["success" => false, "message" => "Username y rol son obligatorios"]);
    exit;
}

if ($has_access === 1) {
    if (!$email || !$password) {
        echo json_encode([
            "success" => false,
            "message" => "Email y contraseña son obligatorios si el usuario tiene acceso"
        ]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Email inválido"]);
        exit;
    }
}

// Validar duplicado de email SOLO si existe
if ($email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Email ya registrado"]);
        exit;
    }
}

// Validar duplicado de username
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo json_encode(["success" => false, "message" => "Username ya existe"]);
    exit;
}

$hashedPassword = $has_access ? password_hash($password, PASSWORD_DEFAULT) : null;
$emailValue     = $has_access ? $email : null;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO users (email, username, password, role_id, has_access, active)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $emailValue,
        $username,
        $hashedPassword,
        $roleId,
        $has_access,
        $active
    ]);

    $userId = $pdo->lastInsertId();

    if (!empty($departments)) {
        $stmtDep = $pdo->prepare("
            INSERT INTO user_departments (user_id, department_id)
            VALUES (?, ?)
        ");

        foreach ($departments as $depId) {
            $stmtDep->execute([$userId, intval($depId)]);
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
        "message" => "Error al crear usuario",
        "error" => $e->getMessage()
    ]);
}
