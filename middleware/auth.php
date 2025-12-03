<?php

require_once __DIR__ . "/../utils/jwt.php";

function require_auth()
{
    $headers = getallheaders();
    $authHeader = $headers["Authorization"] ?? "";

    if (!$authHeader) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "No autorizado: falta Authorization header"
        ]);
        exit;
    }

    // Dividir de forma segura
    $parts = explode(" ", $authHeader);

    if (count($parts) !== 2) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Token mal formado"
        ]);
        exit;
    }

    list($type, $token) = $parts;

    if (strtolower($type) !== "bearer") {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Tipo de token inválido (debe ser Bearer)"
        ]);
        exit;
    }

    $secret = "TU_SECRETO_JWT_AQUI";
    $payload = validate_jwt($token, $secret);

    if (!$payload) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Token expirado o inválido"
        ]);
        exit;
    }

    return $payload; // contiene id, username, role
}

// NUEVO: Validar rol requerido
function require_role($roles = [])
{
    $user = require_auth(); // ya devuelve info del usuario
    if (!in_array($user['role'], $roles)) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No autorizado"]);
        exit();
    }
    return $user;
}
