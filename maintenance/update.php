<?php

require_once "../utils/cors.php";
require_once "../config/database.php";
require_once "../middleware/auth.php";

header("Content-Type: application/json; charset=UTF-8");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$user = require_auth();

// Solo admin
if ($user["role"] !== "admin") {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "No autorizado"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["maintenance"]) || !is_bool($data["maintenance"])) {
    echo json_encode(["success" => false, "error" => "Debe enviar maintenance=true|false"]);
    exit();
}

$value = $data["maintenance"] ? 1 : 0;

$stmt = $pdo->prepare("UPDATE settings SET maintenance = ? WHERE id = 1");
$stmt->execute([$value]);

echo json_encode([
    "success" => true,
    "message" => "Estado actualizado",
    "maintenance" => $data["maintenance"]
]);
