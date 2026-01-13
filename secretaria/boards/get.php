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

// Asistencia completa (elegibles + presentes)
$stmtA = $pdo->prepare("
    SELECT 
        u.id AS user_id,
        u.username,
        COALESCE(ba.present, 0) AS present
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    LEFT JOIN board_attendance ba 
        ON ba.user_id = u.id 
       AND ba.board_id = ?
    WHERE r.name IN ('miembro', 'secretaria', 'pastor', 'ancianos')
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
