<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo admin puede ver usuarios
$admin = require_role(["admin", "secretaria"]);

// Obtener todos los usuarios
$stmt = $pdo->query("
    SELECT 
        u.id,
        u.email,
        u.username,
        u.role_id,
        u.has_access,
        u.active,
        r.name AS role,
        GROUP_CONCAT(d.name SEPARATOR ', ') AS departments_names,
        GROUP_CONCAT(d.id SEPARATOR ',') AS departments_ids
    FROM users u
    LEFT JOIN roles r ON r.id = u.role_id
    LEFT JOIN user_departments ud ON ud.user_id = u.id
    LEFT JOIN departments d ON d.id = ud.department_id
    GROUP BY u.id
    ORDER BY u.username ASC
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$users = array_map(function ($u) {
    return [
        "id" => $u["id"],
        "email" => $u["email"],
        "username" => $u["username"],
        "has_access" => intval($u["has_access"]),
        "active" => intval($u["active"]),
        "role" => $u["role"],
        "role_id" => intval($u["role_id"]),
        "departments" => $u["departments_ids"]
            ? array_map('intval', explode(',', $u["departments_ids"]))
            : [],
        "departments_names" => $u["departments_names"]
    ];
}, $rows);

echo json_encode([
    "success" => true,
    "users" => $users
]);
