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

    /* ==========================
       ACTUALIZAR JUNTA
    ========================== */
    $stmt = $pdo->prepare("
        UPDATE boards
        SET meeting_date = ?, description = ?
        WHERE id = ?
    ");
    $stmt->execute([$meeting_date, $description, $board_id]);

    /* ==========================
       OBTENER ASISTENCIA PREVIA
    ========================== */
    $stmtPrev = $pdo->prepare("
        SELECT user_id, present
        FROM board_attendance
        WHERE board_id = ?
    ");
    $stmtPrev->execute([$board_id]);
    $previousAttendance = $stmtPrev->fetchAll(PDO::FETCH_ASSOC);

    /* ==========================
       INDEXAR ASISTENCIA ENTRANTE
    ========================== */
    $incomingAttendance = [];

    foreach ($attendance as $item) {
        if (!isset($item["user_id"])) {
            throw new Exception("Formato de asistencia inválido");
        }

        $incomingAttendance[(int)$item["user_id"]] =
            !empty($item["present"]) ? 1 : 0;
    }

    /* ==========================
       FUSIONAR (PRESERVAR HISTÓRICOS)
    ========================== */
    $finalAttendance = $incomingAttendance;

    foreach ($previousAttendance as $old) {
        $uid = (int)$old["user_id"];

        if (!array_key_exists($uid, $finalAttendance)) {
            $finalAttendance[$uid] = (int)$old["present"];
        }
    }

    /* ==========================
       REEMPLAZAR ASISTENCIA
    ========================== */
    $pdo->prepare("DELETE FROM board_attendance WHERE board_id = ?")
        ->execute([$board_id]);

    if (!empty($finalAttendance)) {
        $stmtA = $pdo->prepare("
            INSERT INTO board_attendance (board_id, user_id, present)
            VALUES (?, ?, ?)
        ");

        foreach ($finalAttendance as $userId => $present) {
            $stmtA->execute([
                $board_id,
                $userId,
                $present
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
