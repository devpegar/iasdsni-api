<?php

require_once __DIR__ . "/../../../utils/cors.php";
require_once __DIR__ . "/../../../utils/http.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../_helpers.php";

require_method("GET");
require_role(["admin"]);

$doctrineId = (int)($_GET["doctrine_id"] ?? 0);

try {
    $params = [];
    $where = "";

    if ($doctrineId) {
        $where = "WHERE i.doctrine_id = :doctrine_id";
        $params["doctrine_id"] = $doctrineId;
    }

    $stmt = $pdo->prepare("
        SELECT i.id, i.doctrine_id, d.title AS doctrine_title, i.title, i.slug, i.content,
               i.bible_references, i.sort_order, i.is_active, i.created_at, i.updated_at
        FROM belief_items i
        INNER JOIN belief_doctrines d ON d.id = i.doctrine_id
        {$where}
        ORDER BY d.sort_order ASC, i.sort_order ASC, i.title ASC
    ");
    $stmt->execute($params);

    $items = array_map("map_belief_item", $stmt->fetchAll(PDO::FETCH_ASSOC));
    json_success(["items" => $items]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al obtener creencias");
}
