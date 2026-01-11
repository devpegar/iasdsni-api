<?php

require_once __DIR__ . "/env.php";

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowed_origins = [
    "http://localhost:5173",
    "http://localhost",
    "http://localhost:80",
    "https://iasdsni.com.ar",
    "https://www.iasdsni.com.ar"
];

if ($origin && in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
