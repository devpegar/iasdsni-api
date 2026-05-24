<?php

require_once "../../utils/cors.php";
require_once "../../utils/http.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$data = read_json_body();
$id = intval($data["id"] ?? $_GET["id"] ?? 0);

if (!$id) {
    json_error("ID inválido", 422);
}

// Evitar borrar el rol admin
$stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
$stmt->execute([$id]);
$role = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$role) {
    json_error("Rol no encontrado", 404);
}

if ($role["name"] === "admin") {
    json_error("El rol admin no se puede eliminar", 409);
}

// Verificar si está asignado a usuarios
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
$stmt->execute([$id]);
$count = $stmt->fetchColumn();

if ($count > 0) {
    json_error("No se puede eliminar un rol asignado a usuarios", 409);
}

// Eliminar
$stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
$success = $stmt->execute([$id]);

if (!$success) {
    json_error("Error al eliminar rol");
}

json_success(["id" => $id], "Rol eliminado");
