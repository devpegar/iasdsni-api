<?php
require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

require_role(["secretaria", "admin"]);

$board_id = $_GET["board_id"] ?? null;

if (!$board_id) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        ba.user_id,
        ba.present,
        u.username
    FROM board_attendance ba
    JOIN users u ON ba.user_id = u.id
    WHERE ba.board_id = ?
    ORDER BY u.username
");

$stmt->execute([$board_id]);

echo json_encode([
    "success" => true,
    "attendance" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
