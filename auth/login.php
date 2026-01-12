<?php

require_once __DIR__ . "/../utils/env.php";
load_env();

require_once __DIR__ . "/../utils/cors.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../utils/jwt.php";

header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data["username"] ?? "");
$password = $data["password"] ?? "";

if ($username === "" || $password === "") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Credenciales inválidas"
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT users.*, roles.name AS role_name
    FROM users
    LEFT JOIN roles ON roles.id = users.role_id
    WHERE users.username = ?
    LIMIT 1
");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (
    !$user ||
    !password_verify($password, $user["password"]) ||
    (int)$user["has_access"] !== 1
) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Credenciales inválidas"
    ]);
    exit;
}

$secret = env("JWT_SECRET");
if (!$secret) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error interno del servidor"
    ]);
    exit;
}

$token = create_jwt([
    "id"       => $user["id"],
    "username" => $user["username"],
    "role"     => $user["role_name"],
    "role_id"  => $user["role_id"]
], $secret);

// Detectar entorno
$isProduction = env("APP_ENV") === "production";

$cookieOptions = [
    "expires"  => time() + 86400,
    "path"     => "/",
    "httponly" => true,
    "samesite" => "Lax",
];

if ($isProduction) {
    $cookieOptions["domain"] = "iasdsni.com.ar";
    $cookieOptions["secure"] = true;
} else {
    // LOCAL
    $cookieOptions["secure"] = false;
}

setcookie("token", $token, $cookieOptions);


echo json_encode([
    "success" => true,
    "user" => [
        "id"       => $user["id"],
        "username" => $user["username"],
        "role"     => $user["role_name"],
        "role_id"  => $user["role_id"]
    ]
]);
