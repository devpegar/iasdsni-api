<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Solo secretaria o admin
require_role(["secretaria", "admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$meeting_date = trim($data["meeting_date"] ?? "");
$description  = trim($data["description"] ?? "");
$attendance   = $data["attendance"] ?? []; // array de user_id

if (!$meeting_date) {
    echo json_encode([
        "success" => false,
        "message" => "La fecha de la junta es obligatoria"
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Crear junta
    $stmt = $pdo->prepare("
        INSERT INTO boards (meeting_date, description)
        VALUES (?, ?)
    ");
    $stmt->execute([$meeting_date, $description]);
    $boardId = $pdo->lastInsertId();

    // Registrar asistencia (opcional)
    if (!empty($attendance)) {
        $stmtA = $pdo->prepare("
            INSERT INTO board_attendance (board_id, user_id, present)
            VALUES (?, ?, 1)
        ");

        foreach ($attendance as $user_id) {
            $stmtA->execute([$boardId, $user_id]);
        }
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Junta creada correctamente",
        "board_id" => $boardId
    ]);
} catch (Exception $e) {
    $pdo->rollBack();

    echo json_encode([
        "success" => false,
        "message" => "Error al crear la junta",
        "error" => $e->getMessage()
    ]);
}
