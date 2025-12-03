<?php
require_once "../../config.php";
require_once "../../auth/check.php";

$data = json_decode(file_get_contents("php://input"), true);

$vote_id = $data["vote_id"] ?? null;
$user_id = $data["user_id"] ?? null;

if (!$vote_id) {
    echo json_encode(["error" => "vote_id required"]);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE votes
    SET responsible_user_id = ?
    WHERE id = ?
");
$stmt->execute([$user_id, $vote_id]);

echo json_encode(["success" => true]);
