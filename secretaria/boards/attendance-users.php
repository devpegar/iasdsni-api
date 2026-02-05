<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo secretaria / admin
require_role(["admin", "secretaria"]);

$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.username
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    WHERE
        u.active = 1
        AND r.name IN ('miembro', 'secretaria', 'pastor', 'ancianos')
    ORDER BY u.username ASC
");

$stmt->execute();

echo json_encode([
    "success" => true,
    "users" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
