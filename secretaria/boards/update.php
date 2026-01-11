<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Acceso permitido
require_role(["secretaria", "admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$board_id     = $data["id"] ?? null;
$meeting_date = trim($data["meeting_date"] ?? "");
$description  = trim($data["description"] ?? "");
$attendance   = $data["attendance"] ?? [];

if (!$board_id) {
    echo json_encode([
        "success" => false,
        "message" => "ID de junta requerido"
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Actualizar junta
    $stmt = $pdo->prepare("
        UPDATE boards
        SET meeting_date = ?, description = ?
        WHERE id = ?
    ");
    $stmt->execute([$meeting_date, $description, $board_id]);

    // Limpiar asistencia previa
    $pdo->prepare("DELETE FROM board_attendance WHERE board_id = ?")
        ->execute([$board_id]);

    // Registrar asistencia completa
    if (!empty($attendance)) {
        $stmtA = $pdo->prepare("
            INSERT INTO board_attendance (board_id, user_id, present)
            VALUES (?, ?, ?)
        ");

        foreach ($attendance as $item) {
            if (!isset($item["user_id"])) {
                throw new Exception("Formato de asistencia inválido");
            }

            $stmtA->execute([
                $board_id,
                (int)$item["user_id"],
                !empty($item["present"]) ? 1 : 0
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Junta actualizada correctamente"
    ]);
} catch (Exception $e) {
    $pdo->rollBack();

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error al actualizar la junta",
        "error" => $e->getMessage()
    ]);
}
