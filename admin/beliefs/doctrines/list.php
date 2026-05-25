<?php

require_once __DIR__ . "/../../../utils/cors.php";
require_once __DIR__ . "/../../../utils/http.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../_helpers.php";

require_method("GET");
require_role(["admin"]);

$q = trim($_GET["q"] ?? "");

try {
    if ($q !== "") {
        $stmt = $pdo->prepare("
            SELECT id, title, slug, summary, image_url, sort_order, is_active, created_at, updated_at
            FROM belief_doctrines
            WHERE title LIKE :q OR slug LIKE :q
            ORDER BY sort_order ASC, title ASC
        ");
        $stmt->execute(["q" => "%{$q}%"]);
    } else {
        $stmt = $pdo->query("
            SELECT id, title, slug, summary, image_url, sort_order, is_active, created_at, updated_at
            FROM belief_doctrines
            ORDER BY sort_order ASC, title ASC
        ");
    }

    $doctrines = array_map("map_belief_doctrine", $stmt->fetchAll(PDO::FETCH_ASSOC));
    json_success(["doctrines" => $doctrines]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener doctrinas");
}
