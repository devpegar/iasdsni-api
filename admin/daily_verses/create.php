<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$text      = trim($data["text"] ?? "");
$reference = trim($data["reference"] ?? "");
$eop       = trim($data["eop"] ?? "");
$position  = intval($data["position"] ?? 1);
$is_active = isset($data["is_active"]) ? intval($data["is_active"]) : 1;

if (!$text || !$reference) {
    echo json_encode([
        "success" => false,
        "message" => "Texto y referencia son obligatorios"
    ]);
    exit;
}

$is_active = $is_active ? 1 : 0;

try {
    $stmt = $pdo->prepare("
        INSERT INTO daily_verses (
            text,
            reference,
            eop,
            position,
            is_active
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $text,
        $reference,
        $eop ?: null,
        $position,
        $is_active
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Verso creado correctamente"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error al crear verso",
        "error" => $e->getMessage()
    ]);
}
