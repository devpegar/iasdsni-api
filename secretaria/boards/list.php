<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

// secretaria o admin
require_role(["secretaria", "admin"]);

$stmt = $pdo->query("
    SELECT id, meeting_date, description, created_at
    FROM boards
    ORDER BY meeting_date DESC
");

$boards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar asistencia para cada junta
foreach ($boards as &$board) {
    $stmtA = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.email,
            ba.present
        FROM board_attendance ba
        INNER JOIN users u ON u.id = ba.user_id
        WHERE ba.board_id = ?
        ORDER BY u.username ASC
    ");
    $stmtA->execute([$board["id"]]);
    $board["attendance"] = $stmtA->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode([
    "success" => true,
    "boards" => $boards
]);
