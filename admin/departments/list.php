<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Roles permitidos: admin o secretaria
$allowed = require_role(["admin", "secretaria"]);

// Obtener id + name
$stmt = $pdo->query("
    SELECT id, name, description 
    FROM departments
    ORDER BY id ASC
");

$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "departments" => $departments
]);
