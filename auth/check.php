<?php

require_once "../utils/cors.php";
require_once "../middleware/auth.php";

header("Content-Type: application/json");

// Usamos tu función centralizada de autenticación
$user = require_auth();

echo json_encode([
    "success" => true,
    "user" => $user
]);
