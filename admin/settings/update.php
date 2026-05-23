<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("POST");
require_role(["admin"]);

$data = read_json_body();
$settings = $data["settings"] ?? null;

if (!is_array($settings)) {
    json_error("Configuración inválida", 422);
}

try {
    $keys = array_keys($settings);

    if (count($keys) === 0) {
        json_success(["updated" => 0], "No hay cambios para guardar");
    }

    foreach ($keys as $key) {
        if (!is_string($key) || trim($key) === "") {
            json_error("Clave de configuración inválida", 422);
        }
    }

    $placeholders = implode(",", array_fill(0, count($keys), "?"));
    $stmt = $pdo->prepare("
        SELECT setting_key
        FROM site_settings
        WHERE setting_key IN ({$placeholders})
    ");
    $stmt->execute($keys);
    $existingKeys = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $missingKeys = array_values(array_diff($keys, $existingKeys));

    if (count($missingKeys) > 0) {
        json_error("Una o más configuraciones no existen", 422, ["settings" => $missingKeys]);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE site_settings
        SET setting_value = :setting_value
        WHERE setting_key = :setting_key
    ");

    foreach ($settings as $key => $value) {
        $stmt->execute([
            "setting_value" => $value === null ? "" : (string)$value,
            "setting_key" => $key,
        ]);
    }

    $pdo->commit();

    json_success(["updated" => count($settings)], "Configuración actualizada correctamente");
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e->getCode() === "42S02") {
        json_error("La tabla de configuración no existe", 500);
    }

    error_log($e->getMessage());
    json_error("Error al actualizar configuración del sitio");
}
