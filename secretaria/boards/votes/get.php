<?php

require_once "../../../utils/cors.php";
require_once "../../../config/database.php";
require_once "../../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["secretaria", "admin"]);

$id = $_GET["id"] ?? null;

if (!$id) {
    echo json_encode([
        "success" => false,
        "message" => "ID de voto requerido"
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        v.id,
        v.board_id,
        v.vote_number,
        v.vote_year,
        v.description,
        v.responsible_user_id,
        u.username AS responsible_name,
        v.fulfilled_date,
        v.created_at
    FROM votes v
    LEFT JOIN users u ON u.id = v.responsible_user_id
    WHERE v.id = ?
");

$stmt->execute([$id]);
$vote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vote) {
    echo json_encode([
        "success" => false,
        "message" => "Voto no encontrado"
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "vote" => $vote
]);
