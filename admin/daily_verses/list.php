<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$stmt = $pdo->query("
    SELECT
        id,
        text,
        reference,
        eop,
        position,
        is_active,
        created_at,
        updated_at
    FROM daily_verses
    ORDER BY position ASC, id ASC
");

$verses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$verses = array_map(function ($verse) {
    return [
        "id" => intval($verse["id"]),
        "text" => $verse["text"],
        "reference" => $verse["reference"],
        "eop" => $verse["eop"],
        "position" => intval($verse["position"]),
        "is_active" => intval($verse["is_active"]),
        "created_at" => $verse["created_at"],
        "updated_at" => $verse["updated_at"]
    ];
}, $verses);

echo json_encode([
    "success" => true,
    "verses" => $verses
]);
