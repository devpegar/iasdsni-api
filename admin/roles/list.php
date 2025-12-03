<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo admin puede ver lista de roles
$admin = require_role(["admin"]);

$stmt = $pdo->query("SELECT id, name, description FROM roles ORDER BY id ASC");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "roles" => $roles
]);
