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

$stmt = $pdo->prepare("SELECT id, name, description FROM roles WHERE id = ?");
$stmt->execute([$id]);
$role = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$role) {
    echo json_encode(["success" => false, "message" => "Rol no encontrado"]);
    exit;
}

echo json_encode([
    "success" => true,
    "role" => $role
]);
