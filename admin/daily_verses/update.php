<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$id        = intval($data["id"] ?? 0);
$text      = trim($data["text"] ?? "");
$reference = trim($data["reference"] ?? "");
$eop       = trim($data["eop"] ?? "");
$position  = intval($data["position"] ?? 1);
$is_active = isset($data["is_active"]) ? intval($data["is_active"]) : 1;

if (!$id || !$text || !$reference) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan datos obligatorios"
    ]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM daily_verses WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    echo json_encode([
        "success" => false,
        "message" => "El verso no existe"
    ]);
    exit;
}

$is_active = $is_active ? 1 : 0;

try {
    $stmt = $pdo->prepare("
        UPDATE daily_verses
        SET
            text = ?,
            reference = ?,
            eop = ?,
            position = ?,
            is_active = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $text,
        $reference,
        $eop ?: null,
        $position,
        $is_active,
        $id
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Verso actualizado correctamente"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error al actualizar verso",
        "error" => $e->getMessage()
    ]);
}
