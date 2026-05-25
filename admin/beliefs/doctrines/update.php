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
        SELECT id, title, slug, summary, image_url, sort_order, is_active
        FROM belief_doctrines
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(["id" => $id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current) {
        json_error("La doctrina no existe", 404);
    }

    $title = array_key_exists("title", $data) ? trim((string)$data["title"]) : $current["title"];
    $slug = array_key_exists("slug", $data) ? normalize_belief_slug($data["slug"]) : $current["slug"];
    $summary = array_key_exists("summary", $data) ? trim((string)$data["summary"]) : $current["summary"];
    $imageUrl = array_key_exists("image_url", $data) ? trim((string)$data["image_url"]) : $current["image_url"];
    $sortOrder = array_key_exists("sort_order", $data) ? (int)$data["sort_order"] : (int)$current["sort_order"];
    $isActive = array_key_exists("is_active", $data) ? belief_bool($data["is_active"], 1) : (int)$current["is_active"];

    if ($title === "" || $slug === "") {
        json_error("Título y slug son obligatorios", 422);
    }

    $stmt = $pdo->prepare("SELECT id FROM belief_doctrines WHERE slug = :slug AND id <> :id LIMIT 1");
    $stmt->execute(["slug" => $slug, "id" => $id]);

    if ($stmt->fetch()) {
        json_error("Ya existe una doctrina con ese slug", 409);
    }

    $stmt = $pdo->prepare("
        UPDATE belief_doctrines
        SET title = :title,
            slug = :slug,
            summary = :summary,
            image_url = :image_url,
            sort_order = :sort_order,
            is_active = :is_active
        WHERE id = :id
    ");
    $stmt->execute([
        "title" => $title,
        "slug" => $slug,
        "summary" => $summary !== "" ? $summary : null,
        "image_url" => $imageUrl !== "" ? $imageUrl : null,
        "sort_order" => $sortOrder,
        "is_active" => $isActive,
        "id" => $id,
    ]);

    json_success(["id" => $id, "slug" => $slug, "is_active" => $isActive], "Doctrina actualizada correctamente");
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al actualizar doctrina");
}
