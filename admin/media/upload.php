<?php

require_once __DIR__ . "/../../utils/cors.php";
require_once __DIR__ . "/../../utils/http.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../middleware/auth.php";

require_method("POST");
require_role(["admin"]);

function media_table_exists($pdo, $table)
{
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE :table_name");
        $stmt->execute(["table_name" => $table]);
        return (bool)$stmt->fetch(PDO::FETCH_NUM);
    } catch (PDOException $e) {
        return false;
    }
}

function media_column_exists($pdo, $table, $column)
{
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE :column_name");
        $stmt->execute(["column_name" => $column]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

$hasFolders = media_table_exists($pdo, "media_folders") && media_column_exists($pdo, "media_files", "folder_id");
$folderId = null;
$folderName = null;
$folderSlug = null;

if ($hasFolders && isset($_POST["folder_id"]) && trim((string)$_POST["folder_id"]) !== "") {
    $requestedFolderId = (int)$_POST["folder_id"];

    if ($requestedFolderId > 0) {
        $stmt = $pdo->prepare("
            SELECT id, name, slug
            FROM media_folders
            WHERE id = :id AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute(["id" => $requestedFolderId]);
        $folder = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($folder) {
            $folderId = (int)$folder["id"];
            $folderName = $folder["name"];
            $folderSlug = $folder["slug"];
        }
    }
}

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
$relativeBasePath = "uploads/media";

if ($folderSlug) {
    $uploadDir .= "/" . $folderSlug;
    $relativeBasePath .= "/" . $folderSlug;
}

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
    error_log("[media/upload] cannot create uploads/media");
    json_error("No se pudo guardar la imagen");
}

$filename = "media_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4)) . "." . $allowedMimeTypes[$mimeType];
$destination = $uploadDir . "/" . $filename;
$relativePath = $relativeBasePath . "/" . $filename;
$publicUrl = "/" . $relativePath;
$altText = trim($_POST["alt_text"] ?? "");

try {
    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        error_log("[media/upload] move_uploaded_file failed");
        json_error("No se pudo guardar la imagen");
    }

    $columns = "filename, original_name, mime_type, size_bytes, path, public_url, alt_text";
    $values = ":filename, :original_name, :mime_type, :size_bytes, :path, :public_url, :alt_text";
    $params = [
        "filename" => $filename,
        "original_name" => $file["name"],
        "mime_type" => $mimeType,
        "size_bytes" => (int)$file["size"],
        "path" => $relativePath,
        "public_url" => $publicUrl,
        "alt_text" => $altText !== "" ? $altText : null,
    ];

    if ($hasFolders) {
        $columns = "folder_id, " . $columns;
        $values = ":folder_id, " . $values;
        $params["folder_id"] = $folderId;
    }

    $stmt = $pdo->prepare("
        INSERT INTO media_files ({$columns})
        VALUES ({$values})
    ");

    $stmt->execute($params);

    json_success([
        "file" => [
            "id" => (int)$pdo->lastInsertId(),
            "folder_id" => $folderId,
            "folder_name" => $folderName,
            "folder_slug" => $folderSlug,
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
