<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data["id"] ?? 0);
$hasIsActive = isset($data["is_active"]);
$is_active = $hasIsActive ? intval($data["is_active"]) : null;

if (!$id) {
    echo json_encode([
        "success" => false,
        "message" => "ID invalido"
    ]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, is_active FROM hero_slides WHERE id = ?");
$stmt->execute([$id]);
$slide = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$slide) {
    echo json_encode([
        "success" => false,
        "message" => "El slide no existe"
    ]);
    exit;
}

$is_active = $hasIsActive ? ($is_active ? 1 : 0) : (intval($slide["is_active"]) ? 0 : 1);

try {
    $stmt = $pdo->prepare("UPDATE hero_slides SET is_active = ? WHERE id = ?");
    $stmt->execute([$is_active, $id]);

    echo json_encode([
        "success" => true,
        "message" => "Estado del slide actualizado correctamente",
        "is_active" => $is_active
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error al actualizar estado del slide",
        "error" => $e->getMessage()
    ]);
}
