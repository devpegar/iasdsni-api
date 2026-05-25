<?php

require_once __DIR__ . "/../../../utils/cors.php";
require_once __DIR__ . "/../../../utils/http.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../_helpers.php";

require_method("POST");
require_role(["admin"]);

$data = read_json_body();
$id = (int)($data["id"] ?? 0);

if (!$id) {
    json_error("ID inválido", 422);
}

try {
    $stmt = $pdo->prepare("
        SELECT id, doctrine_id, title, slug, content, bible_references, sort_order, is_active
        FROM belief_items
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(["id" => $id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current) {
        json_error("La creencia no existe", 404);
    }

    $doctrineId = array_key_exists("doctrine_id", $data) ? (int)$data["doctrine_id"] : (int)$current["doctrine_id"];
    $title = array_key_exists("title", $data) ? trim((string)$data["title"]) : $current["title"];
    $slug = array_key_exists("slug", $data) ? normalize_belief_slug($data["slug"]) : $current["slug"];
    $content = array_key_exists("content", $data) ? trim((string)$data["content"]) : $current["content"];
    $bibleReferences = array_key_exists("bible_references", $data)
        ? trim((string)$data["bible_references"])
        : $current["bible_references"];
    $sortOrder = array_key_exists("sort_order", $data) ? (int)$data["sort_order"] : (int)$current["sort_order"];
    $isActive = array_key_exists("is_active", $data) ? belief_bool($data["is_active"], 1) : (int)$current["is_active"];

    if (!$doctrineId || $title === "" || $slug === "" || $content === "") {
        json_error("Doctrina, título, slug y texto son obligatorios", 422);
    }

    $stmt = $pdo->prepare("SELECT id FROM belief_doctrines WHERE id = :id LIMIT 1");
    $stmt->execute(["id" => $doctrineId]);

    if (!$stmt->fetch()) {
        json_error("La doctrina indicada no existe", 404);
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM belief_items
        WHERE doctrine_id = :doctrine_id AND slug = :slug AND id <> :id
        LIMIT 1
    ");
    $stmt->execute(["doctrine_id" => $doctrineId, "slug" => $slug, "id" => $id]);

    if ($stmt->fetch()) {
        json_error("Ya existe una creencia con ese slug en la doctrina", 409);
    }

    $stmt = $pdo->prepare("
        UPDATE belief_items
        SET doctrine_id = :doctrine_id,
            title = :title,
            slug = :slug,
            content = :content,
            bible_references = :bible_references,
            sort_order = :sort_order,
            is_active = :is_active
        WHERE id = :id
    ");
    $stmt->execute([
        "doctrine_id" => $doctrineId,
        "title" => $title,
        "slug" => $slug,
        "content" => $content,
        "bible_references" => $bibleReferences !== "" ? $bibleReferences : null,
        "sort_order" => $sortOrder,
        "is_active" => $isActive,
        "id" => $id,
    ]);

    json_success(["id" => $id, "slug" => $slug, "is_active" => $isActive], "Creencia actualizada correctamente");
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al actualizar creencia");
}
