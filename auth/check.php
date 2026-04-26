<?php

require_once __DIR__ . "/../utils/env.php";
load_env();

require_once __DIR__ . "/../utils/cors.php";
require_once __DIR__ . "/../utils/jwt.php";

header("Content-Type: application/json; charset=UTF-8");

$token = $_COOKIE["token"] ?? "";

if (!is_string($token) || trim($token) === "") {
    error_log("[auth/check] token cookie missing");
    echo json_encode(["authenticated" => false]);
    exit;
}

$secret = env("JWT_SECRET");
if (!$secret) {
    error_log("[auth/check] JWT_SECRET missing");
    http_response_code(500);
    echo json_encode([
        "authenticated" => false,
        "error" => "Server misconfiguration"
    ]);
    exit;
}

error_log("[auth/check] token received len=" . strlen($token) . " secret_len=" . strlen($secret) . " token_sha256_12=" . substr(hash('sha256', $token), 0, 12));

$payload = validate_jwt($token, $secret);

if (!$payload || !isset($payload["id"], $payload["role"])) {
    error_log("[auth/check] token rejected");
    echo json_encode(["authenticated" => false]);
    exit;
}

error_log("[auth/check] token accepted user_id={$payload["id"]} role={$payload["role"]}");

echo json_encode([
    "authenticated" => true,
    "user" => [
        "id"       => $payload["id"],
        "username" => $payload["username"] ?? null,
        "role"     => $payload["role"]
    ]
]);
