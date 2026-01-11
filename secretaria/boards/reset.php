<?php

require_once "../utils/cors.php";
require_once "../config/database.php";
require_once "../middleware/auth.php";

header("Content-Type: application/json");

// SOLO admin
require_role("admin");

try {
    $pdo->beginTransaction();

    $pdo->exec("DELETE FROM board_attendance");
    $pdo->exec("DELETE FROM votes");
    $pdo->exec("DELETE FROM boards");

    // Reiniciar autoincrement (MySQL)
    $pdo->exec("ALTER TABLE board_attendance AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE votes AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE boards AUTO_INCREMENT = 1");

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Secretaría reiniciada correctamente"
    ]);
} catch (Exception $e) {
    $pdo->rollBack();

    echo json_encode([
        "success" => false,
        "message" => "Error al reiniciar",
        "error" => $e->getMessage()
    ]);
}
