<?php

require_once "../../utils/cors.php";
require_once "../../config/database.php";
require_once "../../middleware/auth.php";

header("Content-Type: application/json");

require_role(["admin"]);

$fileKey = isset($_FILES["image"]) ? "image" : (isset($_FILES["file"]) ? "file" : null);

if (!$fileKey) {
    echo json_encode([
        "success" => false,
        "message" => "Imagen obligatoria"
    ]);
    exit;
}

$image = $_FILES[$fileKey];

if ($image["error"] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "success" => false,
        "message" => "Error al subir imagen"
    ]);
    exit;
}

$maxSize = 5 * 1024 * 1024;

if ($image["size"] > $maxSize) {
    echo json_encode([
        "success" => false,
        "message" => "La imagen no puede superar 5MB"
    ]);
    exit;
}

$mimeTypes = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp"
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $image["tmp_name"]);
finfo_close($finfo);

if (!isset($mimeTypes[$mimeType]) || !getimagesize($image["tmp_name"])) {
    echo json_encode([
        "success" => false,
        "message" => "Formato de imagen invalido"
    ]);
    exit;
}

$uploadDir = __DIR__ . "/../../uploads/hero/img";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$originalName = pathinfo($image["name"], PATHINFO_FILENAME);
$safeName = preg_replace("/[^a-zA-Z0-9_-]/", "-", strtolower($originalName));
$safeName = trim($safeName, "-") ?: "hero-slide";
$fileName = $safeName . "-" . date("YmdHis") . "-" . bin2hex(random_bytes(4)) . "." . $mimeTypes[$mimeType];
$destination = $uploadDir . "/" . $fileName;

try {
    if (!move_uploaded_file($image["tmp_name"], $destination)) {
        echo json_encode([
            "success" => false,
            "message" => "No se pudo guardar la imagen"
        ]);
        exit;
    }

    $imagePath = "/uploads/hero/img/" . $fileName;

    echo json_encode([
        "success" => true,
        "message" => "Imagen subida correctamente",
        "image_path" => $imagePath
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error al subir imagen",
        "error" => $e->getMessage()
    ]);
}
