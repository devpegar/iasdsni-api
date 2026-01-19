<?php
header("Content-Type: application/json; charset=utf8");

require_once __DIR__ . "/../../utils/env.php";
require_once __DIR__ . "/../../config/database.php";

// Solo GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        "error" => "Método no permitido"
    ]);
    exit;
}

try {
    $sql = "
        SELECT
            id,
            title,
            description,
            button_text,
            button_link,
            image_path
        FROM hero_slides
        WHERE is_active = 1
        ORDER BY position ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $slides = [];

    $baseUrl = rtrim(env('APP_URL', ''), '/');

    // ⚠️ ESTE while es clave
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $slides[] = [
            "id" => (int) $row['id'],
            "title" => $row['title'],
            "description" => $row['description'],
            "button_text" => $row['button_text'],
            "button_link" => $row['button_link'],
            "image_url" => $baseUrl . $row['image_path']
        ];
    }

    http_response_code(200);
    echo json_encode($slides);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Error al obtener los slides",
        "detail" => $e->getMessage()
    ]);
}
