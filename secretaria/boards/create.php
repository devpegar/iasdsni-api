<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// Acceso permitido
require_role(["secretaria", "admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$meeting_date = trim($data["meeting_date"] ?? "");
$description  = trim($data["description"] ?? "");
$attendance   = $data["attendance"] ?? [];

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

    /*
     * Registrar asistencia completa
     * attendance = [{ user_id, present }]
     */
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
                $boardId,
                (int)$item["user_id"],
                !empty($item["present"]) ? 1 : 0
            ]);
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

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error al crear la junta",
        "error" => $e->getMessage()
    ]);
}
