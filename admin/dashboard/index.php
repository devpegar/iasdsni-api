<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("GET");

$user = require_auth();

json_success([
    "user" => $user,
], "Dashboard accesible");
