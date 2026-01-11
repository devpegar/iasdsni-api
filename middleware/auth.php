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
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Error interno del servidor"
        ]);
        exit;
    }

    // Validar token
    $payload = validate_jwt($token, $secret);

    if (!$payload || !isset($payload["role"])) {
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
