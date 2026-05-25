<?php

require_once __DIR__ . "/../../../utils/cors.php";
require_once __DIR__ . "/../../../utils/http.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../_helpers.php";

require_method("POST");
require_role(["admin"]);

$data = read_json_body();
$title = trim($data["title"] ?? "");
$slug = normalize_belief_slug($data["slug"] ?? $title);
$summary = trim($data["summary"] ?? "");
$imageUrl = trim($data["image_url"] ?? "");
$sortOrder = (int)($data["sort_order"] ?? 0);
$isActive = belief_bool($data["is_active"] ?? null, 1);

if ($title === "" || $slug === "") {
    json_error("Título y slug son obligatorios", 422);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM belief_doctrines WHERE slug = :slug LIMIT 1");
    $stmt->execute(["slug" => $slug]);

    if ($stmt->fetch()) {
        json_error("Ya existe una doctrina con ese slug", 409);
    }

    $stmt = $pdo->prepare("
        INSERT INTO belief_doctrines (title, slug, summary, image_url, sort_order, is_active)
        VALUES (:title, :slug, :summary, :image_url, :sort_order, :is_active)
    ");
    $stmt->execute([
        "title" => $title,
        "slug" => $slug,
        "summary" => $summary !== "" ? $summary : null,
        "image_url" => $imageUrl !== "" ? $imageUrl : null,
        "sort_order" => $sortOrder,
        "is_active" => $isActive,
    ]);

    json_success(["id" => (int)$pdo->lastInsertId(), "slug" => $slug], "Doctrina creada correctamente", 201);
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_error("Error al crear doctrina");
}
