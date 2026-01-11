<?php

require_once __DIR__ . "/../utils/env.php";
load_env();

require_once __DIR__ . "/../utils/cors.php";
require_once __DIR__ . "/../utils/jwt.php";

header("Content-Type: application/json; charset=UTF-8");

$token = $_COOKIE["token"] ?? "";

if (!is_string($token) || trim($token) === "") {
    echo json_encode(["authenticated" => false]);
    exit;
}

$secret = env("JWT_SECRET");
if (!$secret) {
    http_response_code(500);
    echo json_encode([
        "authenticated" => false,
        "error" => "Server misconfiguration"
    ]);
    exit;
}

$payload = validate_jwt($token, $secret);

if (!$payload || !isset($payload["id"], $payload["role"])) {
    echo json_encode(["authenticated" => false]);
    exit;
}

echo json_encode([
    "authenticated" => true,
    "user" => [
        "id"       => $payload["id"],
        "username" => $payload["username"] ?? null,
        "role"     => $payload["role"]
    ]
]);
