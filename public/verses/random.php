<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/env.php";
require_once __DIR__ . "/../../config/database.php";

load_env();

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Metodo no permitido"]);
    exit;
}

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
        http_response_code(404);
        echo json_encode(["error" => "No hay versos activos"]);
        exit;
    }

    echo json_encode([
        "id" => (int)$verse["id"],
        "text" => $verse["text"],
        "reference" => $verse["reference"],
        "eop" => $verse["eop"]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Error al obtener verso",
        "detail" => $e->getMessage()
    ]);
}
