<?php
require_once "../../../utils/cors.php";
require_once "../../../utils/http.php";
require_once "../../../config/database.php";
require_once "../../../middleware/auth.php";

require_role(["secretaria", "admin"]);

$data = read_json_body();
$id = (int)($data["id"] ?? $_GET["id"] ?? 0);

if (!$id) {
    json_error("ID inválido", 422);
}

$stmt = $pdo->prepare("DELETE FROM votes WHERE id = ?");
$stmt->execute([$id]);

json_success(["id" => $id], "Voto eliminado correctamente");
