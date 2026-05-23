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

function media_ensure_dir($dir)
{
    return is_dir($dir) || mkdir($dir, 0775, true);
}

function media_gd_available()
{
    return extension_loaded("gd") && function_exists("imagecreatetruecolor");
}

function media_create_image($path, $mimeType)
{
    if ($mimeType === "image/jpeg" && function_exists("imagecreatefromjpeg")) {
        return imagecreatefromjpeg($path);
    }

    if ($mimeType === "image/png" && function_exists("imagecreatefrompng")) {
        return imagecreatefrompng($path);
    }

    if ($mimeType === "image/webp" && function_exists("imagecreatefromwebp")) {
        return imagecreatefromwebp($path);
    }

    return false;
}

function media_save_image($image, $destination, $mimeType, $preferWebp)
{
    if ($preferWebp && function_exists("imagewebp")) {
        return imagewebp($image, $destination, 82);
    }

    if ($mimeType === "image/jpeg" && function_exists("imagejpeg")) {
        return imagejpeg($image, $destination, 85);
    }

    if ($mimeType === "image/png" && function_exists("imagepng")) {
        return imagepng($image, $destination, 6);
    }

    if ($mimeType === "image/webp" && function_exists("imagewebp")) {
        return imagewebp($image, $destination, 82);
    }

    return false;
}

function media_generate_variant($sourcePath, $destination, $mimeType, $maxWidth, $preferWebp)
{
    $sourceSize = getimagesize($sourcePath);

    if (!$sourceSize) {
        return null;
    }

    [$sourceWidth, $sourceHeight] = $sourceSize;
    $targetWidth = min($sourceWidth, $maxWidth);
    $targetHeight = (int)round($sourceHeight * ($targetWidth / $sourceWidth));
    $sourceImage = media_create_image($sourcePath, $mimeType);

    if (!$sourceImage) {
        return null;
    }

    $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

    if ($mimeType === "image/png" || $mimeType === "image/webp") {
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
        imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);
    }

    imagecopyresampled(
        $targetImage,
        $sourceImage,
        0,
        0,
        0,
        0,
        $targetWidth,
        $targetHeight,
        $sourceWidth,
        $sourceHeight
    );

    $saved = media_save_image($targetImage, $destination, $mimeType, $preferWebp);

    imagedestroy($sourceImage);
    imagedestroy($targetImage);

    if (!$saved) {
        return null;
    }

    return [
        "width" => $targetWidth,
        "height" => $targetHeight,
    ];
}

$hasFolders = media_table_exists($pdo, "media_folders") && media_column_exists($pdo, "media_files", "folder_id");
$optimizationColumns = [
    "original_path",
    "original_url",
    "optimized_path",
    "optimized_url",
    "thumbnail_path",
    "thumbnail_url",
    "width",
    "height",
    "optimized_width",
    "optimized_height",
    "optimization_status",
];
$hasOptimizationColumns = count(array_filter($optimizationColumns, function ($column) use ($pdo) {
    return media_column_exists($pdo, "media_files", $column);
})) === count($optimizationColumns);
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

$maxSize = 10 * 1024 * 1024;

if ((int)$file["size"] > $maxSize) {
    json_error("La imagen no puede superar 10MB", 422);
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

if (!media_ensure_dir($uploadDir)) {
    error_log("[media/upload] cannot create uploads/media");
    json_error("No se pudo guardar la imagen");
}

$baseName = "media_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4));
$filename = $baseName . "." . $allowedMimeTypes[$mimeType];
$destination = $uploadDir . "/" . $filename;
$relativePath = $relativeBasePath . "/" . $filename;
$publicUrl = "/" . $relativePath;
$altText = trim($_POST["alt_text"] ?? "");
$originalPath = null;
$originalUrl = null;
$optimizedPath = null;
$optimizedUrl = null;
$thumbnailPath = null;
$thumbnailUrl = null;
$width = null;
$height = null;
$optimizedWidth = null;
$optimizedHeight = null;
$optimizationStatus = "legacy";

try {
    $canOptimize = $hasOptimizationColumns && media_gd_available() && $mimeType !== "image/gif";

    if ($hasOptimizationColumns && $canOptimize) {
        $originalDir = $uploadDir . "/originals";
        $thumbDir = $uploadDir . "/thumbs";

        if (!media_ensure_dir($originalDir) || !media_ensure_dir($thumbDir)) {
            error_log("[media/upload] cannot create media optimization directories");
            json_error("No se pudo guardar la imagen");
        }

        $originalPath = $relativeBasePath . "/originals/" . $filename;
        $originalUrl = "/" . $originalPath;
        $destination = $originalDir . "/" . $filename;
    }

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        error_log("[media/upload] move_uploaded_file failed");
        json_error("No se pudo guardar la imagen");
    }

    $sourceSize = getimagesize($destination);
    if ($sourceSize) {
        $width = (int)$sourceSize[0];
        $height = (int)$sourceSize[1];
    }

    if ($hasOptimizationColumns && !$canOptimize) {
        $originalPath = $relativePath;
        $originalUrl = $publicUrl;
        $optimizationStatus = "skipped";
    }

    if ($canOptimize) {
        $preferWebp = function_exists("imagewebp");
        $outputExtension = $preferWebp ? "webp" : $allowedMimeTypes[$mimeType];
        $optimizedFilename = $baseName . "." . $outputExtension;
        $thumbnailFilename = $baseName . "_thumb." . $outputExtension;
        $optimizedDestination = $uploadDir . "/" . $optimizedFilename;
        $thumbnailDestination = $uploadDir . "/thumbs/" . $thumbnailFilename;
        $optimizedResult = media_generate_variant($destination, $optimizedDestination, $mimeType, 1920, $preferWebp);
        $thumbnailResult = media_generate_variant($destination, $thumbnailDestination, $mimeType, 480, $preferWebp);

        if ($optimizedResult && $thumbnailResult) {
            $filename = $optimizedFilename;
            $relativePath = $relativeBasePath . "/" . $optimizedFilename;
            $publicUrl = "/" . $relativePath;
            $optimizedPath = $relativePath;
            $optimizedUrl = $publicUrl;
            $thumbnailPath = $relativeBasePath . "/thumbs/" . $thumbnailFilename;
            $thumbnailUrl = "/" . $thumbnailPath;
            $optimizedWidth = $optimizedResult["width"];
            $optimizedHeight = $optimizedResult["height"];
            $optimizationStatus = "optimized";
        } else {
            if (file_exists($optimizedDestination)) {
                unlink($optimizedDestination);
            }
            if (file_exists($thumbnailDestination)) {
                unlink($thumbnailDestination);
            }

            $relativePath = $originalPath;
            $publicUrl = $originalUrl;
            $optimizationStatus = "failed";
        }
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

    if ($hasOptimizationColumns) {
        $columns .= ", original_path, original_url, optimized_path, optimized_url, thumbnail_path, thumbnail_url, width, height, optimized_width, optimized_height, optimization_status";
        $values .= ", :original_path, :original_url, :optimized_path, :optimized_url, :thumbnail_path, :thumbnail_url, :width, :height, :optimized_width, :optimized_height, :optimization_status";
        $params["original_path"] = $originalPath;
        $params["original_url"] = $originalUrl;
        $params["optimized_path"] = $optimizedPath;
        $params["optimized_url"] = $optimizedUrl;
        $params["thumbnail_path"] = $thumbnailPath;
        $params["thumbnail_url"] = $thumbnailUrl;
        $params["width"] = $width;
        $params["height"] = $height;
        $params["optimized_width"] = $optimizedWidth;
        $params["optimized_height"] = $optimizedHeight;
        $params["optimization_status"] = $optimizationStatus;
    }

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
            "original_url" => $originalUrl,
            "optimized_url" => $optimizedUrl,
            "thumbnail_url" => $thumbnailUrl,
            "width" => $width,
            "height" => $height,
            "optimized_width" => $optimizedWidth,
            "optimized_height" => $optimizedHeight,
            "optimization_status" => $optimizationStatus,
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
