<?php
require_once __DIR__ . "/../utils/env.php";
load_env();

require_once __DIR__ . "/../utils/cors.php";
require_once __DIR__ . "/../utils/http.php";
require_once __DIR__ . "/../utils/maintenance.php";
require_once __DIR__ . "/../middleware/auth.php";

require_method("POST");

$user = require_auth();

if ($user["role"] !== "admin") {
    json_error("No autorizado", 403);
}

$data = read_json_body();

if (!isset($data["maintenance"]) || !is_bool($data["maintenance"])) {
    json_error("Debe enviar maintenance=true|false", 422);
}

try {
    $flagUpdated = $data["maintenance"]
        ? maintenance_enable_flag()
        : maintenance_disable_flag();

    if (!$flagUpdated) {
        json_error(
            $data["maintenance"]
                ? "No se pudo crear el archivo de mantenimiento"
                : "No se pudo eliminar el archivo de mantenimiento",
            500
        );
    }

    $dbUpdated = maintenance_update_db_state($data["maintenance"]);

    json_success([
        "maintenance" => maintenance_flag_exists(),
        "db_maintenance" => $data["maintenance"],
        "db_updated" => $dbUpdated,
        "source" => "flag",
    ], "Estado actualizado");
} catch (Throwable $e) {
    error_log($e->getMessage());
    json_error("Error al actualizar configuración");
}
