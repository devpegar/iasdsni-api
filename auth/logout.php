<?php

require_once "../utils/cors.php";

setcookie(
    "token",
    "",
    [
        "expires" => time() - 3600,
        "path" => "/",
        "httponly" => true,
        "secure" => false,
        "samesite" => "Lax"
    ]
);

echo json_encode(["success" => true]);
