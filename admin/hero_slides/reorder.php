<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$data = json_decode(file_get_contents("php://input"), true);
$slides = $data["slides"] ?? $data["order"] ?? [];

if (!is_array($slides) || count($slides) === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Orden invalido"
    ]);
    exit;
}

$positions = [];
$ids = [];

foreach ($slides as $index => $slide) {
    if (is_array($slide)) {
        $id = intval($slide["id"] ?? 0);
        $position = isset($slide["position"]) ? intval($slide["position"]) : $index + 1;
    } else {
        $id = intval($slide);
        $position = $index + 1;
    }

    if (!$id) {
        echo json_encode([
            "success" => false,
            "message" => "Orden invalido"
        ]);
        exit;
    }

    if (in_array($id, $ids, true)) {
        echo json_encode([
            "success" => false,
            "message" => "Orden invalido"
        ]);
        exit;
    }

    $ids[] = $id;

    $positions[] = [
        "id" => $id,
        "position" => $position
    ];
}

try {
    $pdo->beginTransaction();

    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM hero_slides WHERE id IN ($placeholders)");
    $stmt->execute($ids);

    if (intval($stmt->fetchColumn()) !== count($ids)) {
        $pdo->rollBack();

        echo json_encode([
            "success" => false,
            "message" => "Uno o mas slides no existen"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE hero_slides SET position = ? WHERE id = ?");

    foreach ($positions as $item) {
        $stmt->execute([
            $item["position"],
            $item["id"]
        ]);
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Orden actualizado correctamente"
    ]);
} catch (Exception $e) {
    $pdo->rollBack();

    echo json_encode([
        "success" => false,
        "message" => "Error al actualizar orden",
        "error" => $e->getMessage()
    ]);
}
