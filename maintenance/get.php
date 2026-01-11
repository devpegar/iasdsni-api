<?php
require_once "../utils/env.php";
load_env();

require_once "../utils/cors.php";
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$stmt = $pdo->query("SELECT maintenance FROM settings WHERE id = 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "maintenance" => (bool)$row["maintenance"]
]);
