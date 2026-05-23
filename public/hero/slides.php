<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/env.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";

load_env();

require_method("GET");

function resolve_hero_url($value, $baseUrl)
{
    $value = trim((string)$value);

    if ($value === "") {
        return "";
    }

    if (
        str_starts_with($value, "http://") ||
        str_starts_with($value, "https://") ||
        str_starts_with($value, "data:") ||
        str_starts_with($value, "blob:")
    ) {
        return $value;
    }

    if (str_starts_with($value, "/uploads/")) {
        return $baseUrl . $value;
    }

    if (str_starts_with($value, "uploads/")) {
        return $baseUrl . "/" . $value;
    }

    return $value;
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
                $buttonUrl = $buttonLink;
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
            "image_path" => $row["image_path"],
            "image_url" => resolve_hero_url($row["image_path"], $baseUrl)
        ];
    }

    echo json_encode($slides);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener slides");
}
