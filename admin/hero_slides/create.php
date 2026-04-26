<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$title       = trim($data["title"] ?? "");
$description = trim($data["description"] ?? "");
$button_text = trim($data["button_text"] ?? "");
$button_link = trim($data["button_link"] ?? "");
$image_path  = trim($data["image_path"] ?? "");
$position    = intval($data["position"] ?? 0);
$is_active   = isset($data["is_active"]) ? intval($data["is_active"]) : 1;

if (!$title || !$image_path) {
    echo json_encode([
        "success" => false,
        "message" => "TÃ­tulo e imagen son obligatorios"
    ]);
    exit;
}

$is_active = $is_active ? 1 : 0;

try {
    $stmt = $pdo->prepare("
        INSERT INTO hero_slides (
            title,
            description,
            button_text,
            button_link,
            image_path,
            position,
            is_active
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $title,
        $description,
        $button_text ?: null,
        $button_link ?: null,
        $image_path,
        $position,
        $is_active
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Slide creado correctamente"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error al crear slide",
        "error" => $e->getMessage()
    ]);
}
