<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("POST");
require_role(["admin"]);

$fileKey = isset($_FILES["image"]) ? "image" : (isset($_FILES["file"]) ? "file" : null);

if (!$fileKey) {
    json_error("Imagen obligatoria", 422);
}

$file = $_FILES[$fileKey];

if ($file["error"] !== UPLOAD_ERR_OK) {
    error_log("[media/upload] upload error code=" . $file["error"]);
    json_error("Error al subir imagen", 422);
}

$maxSize = 3 * 1024 * 1024;

if ((int)$file["size"] > $maxSize) {
    json_error("La imagen no puede superar 3MB", 422);
}

$allowedMimeTypes = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp",
    "image/gif" => "gif",
];
$allowedExtensions = ["jpg", "jpeg", "png", "webp", "gif"];
$extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions, true)) {
    json_error("Formato de imagen inválido", 422);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file["tmp_name"]);
finfo_close($finfo);

if (!isset($allowedMimeTypes[$mimeType]) || !getimagesize($file["tmp_name"])) {
    error_log("[media/upload] invalid image mime=" . (string)$mimeType);
    json_error("Formato de imagen inválido", 422);
}

$uploadDir = __DIR__ . "/../../uploads/media";

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
    error_log("[media/upload] cannot create uploads/media");
    json_error("No se pudo guardar la imagen");
}

$filename = "media_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4)) . "." . $allowedMimeTypes[$mimeType];
$destination = $uploadDir . "/" . $filename;
$relativePath = "uploads/media/" . $filename;
$publicUrl = "/" . $relativePath;
$altText = trim($_POST["alt_text"] ?? "");

try {
    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        error_log("[media/upload] move_uploaded_file failed");
        json_error("No se pudo guardar la imagen");
    }

    $stmt = $pdo->prepare("
        INSERT INTO media_files (
            filename, original_name, mime_type, size_bytes, path, public_url, alt_text
        )
        VALUES (
            :filename, :original_name, :mime_type, :size_bytes, :path, :public_url, :alt_text
        )
    ");

    $stmt->execute([
        "filename" => $filename,
        "original_name" => $file["name"],
        "mime_type" => $mimeType,
        "size_bytes" => (int)$file["size"],
        "path" => $relativePath,
        "public_url" => $publicUrl,
        "alt_text" => $altText !== "" ? $altText : null,
    ]);

    json_success([
        "file" => [
            "id" => (int)$pdo->lastInsertId(),
            "filename" => $filename,
            "original_name" => $file["name"],
            "mime_type" => $mimeType,
            "size_bytes" => (int)$file["size"],
            "path" => $relativePath,
            "public_url" => $publicUrl,
            "alt_text" => $altText,
            "is_active" => 1,
        ],
    ], "Imagen subida correctamente", 201);
} catch (PDOException $e) {
    if (file_exists($destination)) {
        unlink($destination);
    }

    error_log($e->getMessage());
    json_error("Error al registrar imagen");
} catch (Exception $e) {
    if (file_exists($destination)) {
        unlink($destination);
    }

    error_log($e->getMessage());
    json_error("Error al subir imagen");
}
