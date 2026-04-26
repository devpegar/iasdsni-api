<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/env.php";
require_once __DIR__ . "/../../config/database.php";

load_env();

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, title, description, button_text, button_link, image_path
        FROM hero_slides
        WHERE is_active = 1
        ORDER BY position ASC
    ");

    $stmt->execute();

    $baseUrl = rtrim(env('APP_URL', 'http://localhost:8000'), '/');

    $slides = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $buttonLink = $row["button_link"];

        if ($buttonLink) {
            if (
                str_starts_with($buttonLink, "http://") ||
                str_starts_with($buttonLink, "https://") ||
                str_starts_with($buttonLink, "#")
            ) {
                $buttonUrl = $buttonLink;
            } elseif (str_starts_with($buttonLink, "/uploads/")) {
                $buttonUrl = $baseUrl . $buttonLink;
            } else {
                $buttonUrl = $buttonLink; // rutas internas públicas
            }
        } else {
            $buttonUrl = null;
        }

        $slides[] = [
            "id" => (int)$row["id"],
            "title" => $row["title"],
            "description" => $row["description"],
            "button_text" => $row["button_text"],
            "button_url" => $buttonUrl,
            "image_url" => $baseUrl . $row["image_path"]
        ];
    }

    echo json_encode($slides);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Error al obtener slides",
        "detail" => $e->getMessage()
    ]);
}
