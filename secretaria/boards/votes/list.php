<?php
require_once "../../config.php";
require_once "../../auth/check.php";

$board_id = $_GET["board_id"] ?? null;

if (!$board_id) {
    echo json_encode(["error" => "board_id required"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT v.*, u.name AS responsible_name
    FROM votes v
    LEFT JOIN users u ON v.responsible_user_id = u.id
    WHERE v.board_id = ?
    ORDER BY v.vote_number ASC
");
$stmt->execute([$board_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
