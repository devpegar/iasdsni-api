<?php
require_once "../../config.php";
require_once "../../auth/check.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id"] ?? null;
$description = $data["description"] ?? null;

if (!$id || !$description) {
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE votes
    SET description = ?
    WHERE id = ?
");
$stmt->execute([$description, $id]);

echo json_encode(["success" => true]);
