<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo admin y secretaria pueden ver información
require_role(["admin", "secretaria"]);

$id = intval($_GET["id"] ?? 0);

if (!$id) {
    echo json_encode(["success" => false, "message" => "ID inválido"]);
    exit;
}

// Obtener datos del usuario
$stmt = $pdo->prepare("
    SELECT u.id, u.email, u.username, u.has_access, u.active, u.role_id, r.name AS role_name
    FROM users u
    LEFT JOIN roles r ON r.id = u.role_id
    WHERE u.id = ?
");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Usuario no encontrado"]);
    exit;
}

// Obtener IDs de departamentos asociados
$stmt = $pdo->prepare("
    SELECT department_id
    FROM user_departments
    WHERE user_id = ?
");
$stmt->execute([$id]);
$departments_raw = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Asignar array de IDs al usuario
$user["departments"] = array_map("intval", $departments_raw);

echo json_encode([
    "success" => true,
    "user" => $user
]);
