<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Acceso permitido
require_role(["admin", "secretaria"]);

$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.username,
        r.name AS role
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    WHERE r.name IN ('miembro', 'secretaria', 'pastor', 'ancianos')
    ORDER BY u.username ASC
");

$stmt->execute();

echo json_encode([
    "success" => true,
    "users" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
