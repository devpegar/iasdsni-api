<?php

require_once __DIR__ . "/../../../utils/cors.php";
require_once __DIR__ . "/../../../utils/http.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../_helpers.php";

require_method("POST");
require_role(["admin"]);

$data = read_json_body();
$doctrineId = (int)($data["doctrine_id"] ?? 0);
$title = trim($data["title"] ?? "");
$slug = normalize_belief_slug($data["slug"] ?? $title);
$content = trim($data["content"] ?? "");
$bibleReferences = trim($data["bible_references"] ?? "");
$sortOrder = (int)($data["sort_order"] ?? 0);
$isActive = belief_bool($data["is_active"] ?? null, 1);

if (!$doctrineId || $title === "" || $slug === "" || $content === "") {
    json_error("Doctrina, título, slug y texto son obligatorios", 422);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM belief_doctrines WHERE id = :id LIMIT 1");
    $stmt->execute(["id" => $doctrineId]);

    if (!$stmt->fetch()) {
        json_error("La doctrina indicada no existe", 404);
    }

    $stmt = $pdo->prepare("SELECT id FROM belief_items WHERE doctrine_id = :doctrine_id AND slug = :slug LIMIT 1");
    $stmt->execute(["doctrine_id" => $doctrineId, "slug" => $slug]);

    if ($stmt->fetch()) {
        json_error("Ya existe una creencia con ese slug en la doctrina", 409);
    }

    $stmt = $pdo->prepare("
        INSERT INTO belief_items (doctrine_id, title, slug, content, bible_references, sort_order, is_active)
        VALUES (:doctrine_id, :title, :slug, :content, :bible_references, :sort_order, :is_active)
    ");
    $stmt->execute([
        "doctrine_id" => $doctrineId,
        "title" => $title,
        "slug" => $slug,
        "content" => $content,
        "bible_references" => $bibleReferences !== "" ? $bibleReferences : null,
        "sort_order" => $sortOrder,
        "is_active" => $isActive,
    ]);

    json_success(["id" => (int)$pdo->lastInsertId(), "slug" => $slug], "Creencia creada correctamente", 201);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al crear creencia");
}
