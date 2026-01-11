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

    /*
     * VALIDACIÓN DE ASISTENTES
     * Solo roles: miembro y secretaria
     */
    if (!empty($attendance)) {
        $stmtCheck = $pdo->prepare("
            SELECT COUNT(*)
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE u.id = ?
              AND r.name IN ('miembro', 'secretaria')
        ");

        foreach ($attendance as $user_id) {
            $stmtCheck->execute([$user_id]);

            if ($stmtCheck->fetchColumn() == 0) {
                throw new Exception(
                    "Uno o más usuarios no están habilitados para asistir a juntas"
                );
            }
        }
    }

    // Crear junta
    $stmt = $pdo->prepare("
        INSERT INTO boards (meeting_date, description)
        VALUES (?, ?)
    ");
    $stmt->execute([$meeting_date, $description]);

    $boardId = $pdo->lastInsertId();

    // Registrar asistencia
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
        "message" => $e->getMessage()
    ]);
}
