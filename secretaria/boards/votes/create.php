<?php
require_once "../../../utils/cors.php";
require_once "../../../config/database.php";
require_once "../../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["secretaria", "admin"]);

$data = json_decode(file_get_contents("php://input"), true);

$board_id    = $data["board_id"] ?? null;
$description = trim($data["description"] ?? "");

if (!$board_id || !$description) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Año actual
    $year = (int)date("Y");

    // Obtener próximo número correlativo del año
    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(vote_number), 0) + 1
        FROM votes
        WHERE vote_year = ?
    ");
    $stmt->execute([$year]);
    $vote_number = (int)$stmt->fetchColumn();

    // Insertar voto
    $stmt = $pdo->prepare("
        INSERT INTO votes (board_id, vote_number, vote_year, description)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $board_id,
        $vote_number,
        $year,
        $description
    ]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "vote_id" => $pdo->lastInsertId(),
        "vote_number" => $vote_number,
        "vote_year" => $year
    ]);
} catch (Exception $e) {
    $pdo->rollBack();

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error al crear el voto",
        "error" => $e->getMessage()
    ]);
}
