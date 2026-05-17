<?php
require_once __DIR__ . "/../utils/env.php";
load_env();

require_once __DIR__ . "/../utils/cors.php";
require_once __DIR__ . "/../utils/http.php";
require_once __DIR__ . "/../config/database.php";

require_method("GET");

try {
    $stmt = $pdo->query("SELECT maintenance FROM settings WHERE id = 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    json_success([
        "maintenance" => (bool)($row["maintenance"] ?? false),
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener configuración");
}
