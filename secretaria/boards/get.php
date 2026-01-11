<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["secretaria", "admin"]);

$id = $_GET["id"] ?? null;

if (!$id) {
    echo json_encode([
        "success" => false,
        "message" => "ID de junta requerido"
    ]);
    exit;
}

// Junta
$stmt = $pdo->prepare("
    SELECT id, meeting_date, description, created_at
    FROM boards
    WHERE id = ?
");
$stmt->execute([$id]);
$board = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$board) {
    echo json_encode([
        "success" => false,
        "message" => "Junta no encontrada"
    ]);
    exit;
}

// Asistencia
$stmtA = $pdo->prepare("
    SELECT 
        u.id,
        u.username,
        ba.present
    FROM board_attendance ba
    INNER JOIN users u ON u.id = ba.user_id
    WHERE ba.board_id = ?
    ORDER BY u.username
");
$stmtA->execute([$id]);

$board["attendance"] = $stmtA->fetchAll(PDO::FETCH_ASSOC);

// Conteo de votos (opcional pero útil)
$stmtV = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM votes
    WHERE board_id = ?
");
$stmtV->execute([$id]);
$board["votes_count"] = (int)$stmtV->fetchColumn();

echo json_encode([
    "success" => true,
    "board" => $board
]);
