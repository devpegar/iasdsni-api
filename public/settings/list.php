<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";

require_method("GET");

try {
    $stmt = $pdo->query("
        SELECT setting_key, setting_value
        FROM site_settings
        WHERE is_public = 1
        ORDER BY group_name ASC, sort_order ASC, id ASC
    ");

    $settings = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $settings[$row["setting_key"]] = $row["setting_value"] ?? "";
    }

    json_success(["data" => count($settings) > 0 ? $settings : new stdClass()]);
} catch (PDOException $e) {
    if ($e->getCode() === "42S02") {
        json_success(["data" => new stdClass()]);
    }

    error_log($e->getMessage());
    json_success(["data" => new stdClass()]);
}
