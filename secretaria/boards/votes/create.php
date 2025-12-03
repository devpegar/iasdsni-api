<?php
require_once "../../config.php";
require_once "../../auth/check.php";

$data = json_decode(file_get_contents("php://input"), true);

$board_id = $data["board_id"] ?? null;
$vote_number = $data["vote_number"] ?? null;
$vote_year = $data["vote_year"] ?? null;
$description = $data["description"] ?? null;

if (!$board_id || !$vote_number || !$vote_year || !$description) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO votes (board_id, vote_number, vote_year, description)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$board_id, $vote_number, $vote_year, $description]);

echo json_encode(["success" => true, "vote_id" => $pdo->lastInsertId()]);
