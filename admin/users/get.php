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
    SELECT u.id, u.email, u.username, u.role_id, r.name AS role_name
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

// Obtener departamentos
$stmt = $pdo->prepare("
    SELECT d.id, d.name
    FROM user_departments ud
    JOIN departments d ON d.id = ud.department_id
    WHERE ud.user_id = ?
");
$stmt->execute([$id]);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user["departments"] = $departments;

echo json_encode([
    "success" => true,
    "user" => $user
]);
