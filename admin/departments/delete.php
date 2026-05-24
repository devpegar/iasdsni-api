<?php

require_once "../../utils/cors.php";
require_once "../../utils/http.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo admin
$admin = require_role(["admin"]);

$data = read_json_body();

$id = intval($data["id"] ?? $_GET["id"] ?? 0);

if (!$id) {
    json_error("ID inválido", 422);
}

// Verificar que existe
$stmt = $pdo->prepare("SELECT id FROM departments WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    json_error("El departamento no existe", 404);
}

// Evitar borrar si está asignado a usuarios
$stmt = $pdo->prepare("
    SELECT user_id FROM user_departments WHERE department_id = ?
");
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    json_error("No puede eliminarse: hay usuarios asignados", 409);
}

// Borrar
$stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
$stmt->execute([$id]);

json_success(["id" => $id], "Departamento eliminado correctamente");
