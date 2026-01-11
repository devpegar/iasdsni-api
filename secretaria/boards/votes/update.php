<?php
require_once "../../../utils/cors.php";
require_once "../../../config/database.php";
require_once "../../../middleware/auth.php";

require_role(["secretaria", "admin"]);

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
