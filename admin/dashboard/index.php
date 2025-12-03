<?php

require_once "../../utils/cors.php";
require_once "../../middleware/auth.php";

$user = require_auth();

echo json_encode([
    "success" => true,
    "message" => "Dashboard accesible",
    "user" => $user
]);
