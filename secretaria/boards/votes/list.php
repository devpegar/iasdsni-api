<?php

require_once "../../../utils/cors.php";
require_once "../../../config/database.php";
require_once "../../../middleware/auth.php";

require_role(["secretaria", "admin"]);

$board_id = $_GET["board_id"] ?? null;

if (!$board_id) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        v.id,
        v.board_id,
        v.vote_number,
        v.vote_year,
        v.description,
        v.fulfilled_date,
        v.responsible_user_id,
        u.username AS responsible_name
    FROM votes v
    LEFT JOIN users u ON v.responsible_user_id = u.id
    WHERE v.board_id = ?
    ORDER BY v.vote_number ASC
");

$stmt->execute([$board_id]);

echo json_encode([
    "success" => true,
    "votes" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
