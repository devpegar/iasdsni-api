<?php

require_once "../utils/cors.php";
require_once "../config/database.php";
require_once "../utils/jwt.php";

$data = json_decode(file_get_contents("php://input"), true);

$username = $data["username"] ?? "";
$password = $data["password"] ?? "";

if (!$username || !$password) {
    echo json_encode(["success" => false, "message" => "Usuario y contraseña requeridos"]);
    exit;
}

// Traemos role_id y el nombre del rol
$stmt = $pdo->prepare("
    SELECT 
        users.*,
        roles.name AS role_name
    FROM users
    LEFT JOIN roles ON roles.id = users.role_id
    WHERE users.username = ?
");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user["password"])) {
    echo json_encode(["success" => false, "message" => "Credenciales incorrectas"]);
    exit;
}

// Crear JWT
$secret = "TU_SECRETO_JWT_AQUI";

$token = create_jwt([
    "id"       => $user["id"],
    "username" => $user["username"],
    "role_id"  => $user["role_id"],
    "role"     => $user["role_name"]  // nombre del rol
], $secret);

echo json_encode([
    "success" => true,
    "message" => "Login correcto",
    "token" => $token,
    "user" => [
        "id"       => $user["id"],
        "username" => $user["username"],
        "role_id"  => $user["role_id"],
        "role"     => $user["role_name"]
    ]
]);
