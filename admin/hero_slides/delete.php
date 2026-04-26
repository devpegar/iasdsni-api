<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data["id"] ?? $_GET["id"] ?? 0);

if (!$id) {
    echo json_encode([
        "success" => false,
        "message" => "ID invalido"
    ]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM hero_slides WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    echo json_encode([
        "success" => false,
        "message" => "El slide no existe"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM hero_slides WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode([
        "success" => true,
        "message" => "Slide eliminado correctamente"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error al eliminar slide",
        "error" => $e->getMessage()
    ]);
}
