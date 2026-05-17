<?php
require_once __DIR__ . "/../utils/cors.php";
require_once __DIR__ . "/../utils/http.php";
require_once __DIR__ . "/../config/database.php";
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
    $value = $data["maintenance"] ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE settings SET maintenance = ? WHERE id = 1");
    $stmt->execute([$value]);

    json_success([
        "maintenance" => $data["maintenance"],
    ], "Estado actualizado");
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al actualizar configuración");
}
