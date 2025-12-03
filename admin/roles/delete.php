<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$id = intval($_GET["id"] ?? 0);

if (!$id) {
    echo json_encode(["success" => false, "message" => "ID inválido"]);
    exit;
}

// Evitar borrar el rol admin
$stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
$stmt->execute([$id]);
$role = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$role) {
    echo json_encode(["success" => false, "message" => "Rol no encontrado"]);
    exit;
}

if ($role["name"] === "admin") {
    echo json_encode(["success" => false, "message" => "El rol admin no se puede eliminar"]);
    exit;
}

// Verificar si está asignado a usuarios
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
$stmt->execute([$id]);
$count = $stmt->fetchColumn();

if ($count > 0) {
    echo json_encode([
        "success" => false,
        "message" => "No se puede eliminar un rol asignado a usuarios"
    ]);
    exit;
}

// Eliminar
$stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
$success = $stmt->execute([$id]);

echo json_encode([
    "success" => $success,
    "message" => $success ? "Rol eliminado" : "Error al eliminar rol"
]);
