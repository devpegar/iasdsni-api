<?php

require_once "../utils/env.php";
load_env();

require_once "../utils/cors.php";

$isProduction = env("APP_ENV") === "production";

setcookie(
    "token",
    "",
    [
        "expires"  => time() - 3600,
        "path"     => "/",
        "domain"   => $isProduction ? "iasdsni.com.ar" : "",
        "secure"   => $isProduction,
        "httponly" => true,
        "samesite" => "Lax"
    ]
);

echo json_encode(["success" => true]);
