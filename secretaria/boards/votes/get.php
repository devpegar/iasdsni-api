<?php
require_once "../../config.php";
require_once "../../auth/check.php";

$id = $_GET["id"] ?? null;

if (!$id) {
    echo json_encode(["error" => "id required"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT v.*, u.name AS responsible_name
    FROM votes v
    LEFT JOIN users u ON v.responsible_user_id = u.id
    WHERE v.id = ?
");
$stmt->execute([$id]);

echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
