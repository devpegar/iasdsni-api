<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// secretaria o admin
require_role(["secretaria", "admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$board_id     = $data["id"] ?? null;
$meeting_date = trim($data["meeting_date"] ?? "");
$description  = trim($data["description"] ?? "");
$attendance   = $data["attendance"] ?? [];

if (!$board_id) {
    echo json_encode([
        "success" => false,
        "message" => "El ID de la junta es obligatorio"
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Actualizar datos básicos
    $stmt = $pdo->prepare("
        UPDATE boards
        SET meeting_date = ?, description = ?
        WHERE id = ?
    ");
    $stmt->execute([$meeting_date, $description, $board_id]);

    // Reset de asistencia
    $pdo->prepare("DELETE FROM board_attendance WHERE board_id = ?")
        ->execute([$board_id]);

    // Insertar nueva asistencia
    if (!empty($attendance)) {
        $stmtA = $pdo->prepare("
            INSERT INTO board_attendance (board_id, user_id, present)
            VALUES (?, ?, 1)
        ");

        foreach ($attendance as $user_id) {
            $stmtA->execute([$board_id, $user_id]);
        }
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Junta actualizada correctamente"
    ]);
} catch (Exception $e) {
    $pdo->rollBack();

    echo json_encode([
        "success" => false,
        "message" => "Error al actualizar la junta",
        "error" => $e->getMessage()
    ]);
}
