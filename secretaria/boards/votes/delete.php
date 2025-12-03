<?php
require_once "../../config.php";
require_once "../../auth/check.php";

$id = $_GET["id"] ?? null;

if (!$id) {
    echo json_encode(["error" => "id required"]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM votes WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(["success" => true]);
