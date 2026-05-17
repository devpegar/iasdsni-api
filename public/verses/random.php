<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/env.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";

load_env();

require_method("GET");

try {
    $stmt = $pdo->prepare("
        SELECT id, text, reference, eop
        FROM daily_verses
        WHERE is_active = 1
        ORDER BY RAND()
        LIMIT 1
    ");

    $stmt->execute();
    $verse = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$verse) {
        json_error("No hay versos activos", 404);
    }

    echo json_encode([
        "id" => (int)$verse["id"],
        "text" => $verse["text"],
        "reference" => $verse["reference"],
        "eop" => $verse["eop"]
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener verso");
}
