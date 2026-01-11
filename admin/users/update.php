<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo admin puede editar usuarios
require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data["id"] ?? 0);
$email = trim($data["email"] ?? "");
$username = trim($data["username"] ?? "");
$password = trim($data["password"] ?? "");
$role_id = intval($data["role_id"] ?? 0);
$departments = $data["departments"] ?? []; // array de int
$has_access = intval($data["has_access"] ?? 0);


// Username y rol siempre obligatorios
if (!$id || !$username || !$role_id) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan datos obligatorios"
    ]);
    exit;
}

// Si tiene acceso, email obligatorio
if ($has_access === 1 && !$email) {
    echo json_encode([
        "success" => false,
        "message" => "El correo es obligatorio si el usuario tiene acceso"
    ]);
    exit;
}

// Si tiene acceso, validar email
if ($has_access === 0) {
    $email = null;
}


// Evitar duplicados de email
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->execute([$email, $id]);
if ($stmt->fetch()) {
    echo json_encode(["success" => false, "message" => "Email ya registrado"]);
    exit;
}

// Evitar duplicados de username
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
$stmt->execute([$username, $id]);
if ($stmt->fetch()) {
    echo json_encode(["success" => false, "message" => "Nombre de usuario ya existe"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Construir SQL dinámico
    $sql = "UPDATE users SET email = ?, username = ?, role_id = ?, has_access = ?";
    $params = [$email, $username, $role_id, $has_access];


    // Si envía contraseña, actualizarla
    if (!empty($password)) {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    // Actualizar usuario
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Actualizar departamentos
    $pdo->prepare("DELETE FROM user_departments WHERE user_id = ?")
        ->execute([$id]);

    $stmtDep = $pdo->prepare("
        INSERT INTO user_departments (user_id, department_id)
        VALUES (?, ?)
    ");

    foreach ($departments as $dep) {
        $stmtDep->execute([$id, intval($dep)]);
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Usuario actualizado correctamente"
    ]);
} catch (Exception $e) {
    $pdo->rollBack();

    echo json_encode([
        "success" => false,
        "message" => "Error al actualizar usuario",
        "error" => $e->getMessage()
    ]);
}
