<?php
require_once "../../../utils/cors.php";
require_once "../../../config/database.php";
require_once "../../../middleware/auth.php";

require_role(["secretaria", "admin"]);

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$vote_id = $data["vote_id"] ?? null;
$user_id = $data["user_id"] ?? null;

if (!$vote_id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "vote_id required"]);
    exit;
}

/*
 |-----------------------------------------
 | Si hay user_id, validamos que el rol sea válido
 |-----------------------------------------
*/
if ($user_id) {
    $stmt = $pdo->prepare("
        SELECT u.id
        FROM users u
        INNER JOIN roles r ON r.id = u.role_id
        WHERE u.id = ?
        AND r.name IN ('secretaria', 'miembro', 'pastor', 'ancianos')
    ");
    $stmt->execute([$user_id]);

    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Usuario no autorizado como responsable"
        ]);
        exit;
    }
}

/*
 |-----------------------------------------
 | UPDATE (NULL permitido)
 |-----------------------------------------
*/
$stmt = $pdo->prepare("
    UPDATE votes
    SET responsible_user_id = ?
    WHERE id = ?
");

$stmt->execute([
    $user_id ?: null,
    $vote_id
]);

echo json_encode(["success" => true]);
