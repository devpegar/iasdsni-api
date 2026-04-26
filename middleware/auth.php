<?php

require_once __DIR__ . "/../utils/env.php";
load_env();
require_once __DIR__ . "/../utils/jwt.php";

/**
 * Autenticación basada SOLO en cookie HttpOnly "token"
 * Se asume que load_env() ya fue llamado en index.php
 */
function require_auth()
{
    header("Content-Type: application/json; charset=UTF-8");

    // Obtener cookie
    $token = $_COOKIE["token"] ?? "";

    if (!is_string($token) || trim($token) === "") {
        error_log("[middleware/auth] token cookie missing");
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "No autorizado"
        ]);
        exit;
    }

    // Obtener JWT_SECRET desde .env
    $secret = env("JWT_SECRET");

    if (!$secret) {
        error_log("[middleware/auth] JWT_SECRET missing");
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Error interno del servidor"
        ]);
        exit;
    }

    // Validar token
    error_log("[middleware/auth] token received len=" . strlen($token) . " secret_len=" . strlen($secret) . " token_sha256_12=" . substr(hash('sha256', $token), 0, 12));
    $payload = validate_jwt($token, $secret);

    if (!$payload || !isset($payload["role"])) {
        error_log("[middleware/auth] token rejected");
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "No autorizado"
        ]);
        exit;
    }

    return $payload; // id, username, role
}

/**
 * Requiere que el usuario tenga uno de los roles permitidos
 */
function require_role(array $roles)
{
    $user = require_auth();

    if (!in_array($user["role"], $roles, true)) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Acceso denegado"
        ]);
        exit;
    }

    return $user;
}
