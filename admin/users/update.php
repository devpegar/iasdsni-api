<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$id          = intval($data["id"] ?? 0);
$email       = trim($data["email"] ?? "");
$username    = trim($data["username"] ?? "");
$password    = trim($data["password"] ?? "");
$role_id     = intval($data["role_id"] ?? 0);
$departments = $data["departments"] ?? [];
$has_access  = intval($data["has_access"] ?? 0);
$active      = isset($data["active"]) ? intval($data["active"]) : 1;

if (!$id || !$username || !$role_id) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan datos obligatorios"
    ]);
    exit;
}

if ($has_access === 1 && !$email) {
    echo json_encode([
        "success" => false,
        "message" => "El correo es obligatorio si el usuario tiene acceso"
    ]);
    exit;
}

if ($has_access === 0) {
    $email = null;
}

// Duplicado email
if ($email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Email ya registrado"]);
        exit;
    }
}

// Duplicado username
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
$stmt->execute([$username, $id]);
if ($stmt->fetch()) {
    echo json_encode(["success" => false, "message" => "Nombre de usuario ya existe"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $sql = "
        UPDATE users
        SET email = ?, username = ?, role_id = ?, has_access = ?, active = ?
    ";
    $params = [$email, $username, $role_id, $has_access, $active];

    if (!empty($password)) {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Actualizar departamentos
    $pdo->prepare("DELETE FROM user_departments WHERE user_id = ?")
        ->execute([$id]);

    if (!empty($departments)) {
        $stmtDep = $pdo->prepare("
            INSERT INTO user_departments (user_id, department_id)
            VALUES (?, ?)
        ");

        foreach ($departments as $dep) {
            $stmtDep->execute([$id, intval($dep)]);
        }
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
