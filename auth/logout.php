<?php

require_once "../utils/env.php";
load_env();

require_once "../utils/cors.php";

$isProduction = env("APP_ENV") === "production";

$cookieOptions = [
    "expires"  => time() + 86400,
    "path"     => "/",
    "httponly" => true,
    "samesite" => "Lax",
];

if ($isProduction) {
    $cookieOptions["domain"] = "iasdsni.com.ar";
    $cookieOptions["secure"] = true;
} else {
    // LOCAL
    $cookieOptions["secure"] = false;
}

setcookie("token", "", $cookieOptions);

echo json_encode(["success" => true]);
