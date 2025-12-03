<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo admin puede ver usuarios
$admin = require_role(["admin"]);

// Obtener todos los usuarios
$stmt = $pdo->query("
    SELECT 
        u.id,
        u.email,
        u.username,
        r.name AS role,
        GROUP_CONCAT(d.name SEPARATOR ', ') AS departments
    FROM users u
    LEFT JOIN roles r ON r.id = u.role_id
    LEFT JOIN user_departments ud ON ud.user_id = u.id
    LEFT JOIN departments d ON d.id = ud.department_id
    GROUP BY u.id
    ORDER BY u.username ASC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "users" => $users
]);
