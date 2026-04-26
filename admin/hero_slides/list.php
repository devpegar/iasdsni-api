<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$stmt = $pdo->query("
    SELECT
        id,
        title,
        description,
        button_text,
        button_link,
        image_path,
        position,
        is_active,
        created_at,
        updated_at
    FROM hero_slides
    ORDER BY position ASC, id ASC
");

$slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

$slides = array_map(function ($slide) {
    return [
        "id" => intval($slide["id"]),
        "title" => $slide["title"],
        "description" => $slide["description"],
        "button_text" => $slide["button_text"],
        "button_link" => $slide["button_link"],
        "image_path" => $slide["image_path"],
        "position" => intval($slide["position"]),
        "is_active" => intval($slide["is_active"]),
        "created_at" => $slide["created_at"],
        "updated_at" => $slide["updated_at"]
    ];
}, $slides);

echo json_encode([
    "success" => true,
    "slides" => $slides
]);
