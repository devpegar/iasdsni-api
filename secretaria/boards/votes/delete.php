<?php
require_once "../../../utils/cors.php";
require_once "../../../config/database.php";
require_once "../../../middleware/auth.php";

require_role(["secretaria", "admin"]);


$id = $_GET["id"] ?? null;

if (!$id) {
    echo json_encode(["error" => "id required"]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM votes WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(["success" => true]);
