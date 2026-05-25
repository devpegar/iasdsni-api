<?php
require_once __DIR__ . "/../utils/env.php";
load_env();

require_once __DIR__ . "/../utils/cors.php";
require_once __DIR__ . "/../utils/http.php";
require_once __DIR__ . "/../utils/maintenance.php";

require_method("GET");

$dbMaintenance = maintenance_db_state();

json_success([
    "maintenance" => maintenance_flag_exists(),
    "db_maintenance" => $dbMaintenance,
    "source" => "flag",
    "db_available" => $dbMaintenance !== null,
]);
