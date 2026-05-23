<?php

require_once __DIR__ . "/../../config/database.php";

header("Content-Type: application/xml; charset=UTF-8");

function xml_escape($value)
{
    return htmlspecialchars((string)$value, ENT_XML1 | ENT_COMPAT, "UTF-8");
}

function normalize_site_url($url)
{
    $url = trim((string)$url);

    if ($url === "") {
        return "";
    }

    return rtrim($url, "/");
}

function format_lastmod($value)
{
    if (!$value) {
        return null;
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return null;
    }

    return date("c", $timestamp);
}

function table_exists($pdo, $table)
{
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE :table_name");
        $stmt->execute(["table_name" => $table]);
        return (bool)$stmt->fetch(PDO::FETCH_NUM);
    } catch (PDOException $e) {
        return false;
    }
}

function get_table_columns($pdo, $table)
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM {$table}");
        $columns = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[$column["Field"]] = true;
        }

        return $columns;
    } catch (PDOException $e) {
        return [];
    }
}

function get_site_url($pdo)
{
    if (table_exists($pdo, "site_settings")) {
        try {
            $stmt = $pdo->prepare("
                SELECT setting_value
                FROM site_settings
                WHERE setting_key = 'site_url'
                LIMIT 1
            ");
            $stmt->execute();
            $value = normalize_site_url($stmt->fetchColumn());

            if ($value !== "") {
                return $value;
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }
    }

    $appUrl = normalize_site_url(env("APP_URL", ""));

    return $appUrl !== "" ? $appUrl : "http://localhost:8000";
}

function add_url(&$urls, $baseUrl, $path, $lastmod, $changefreq, $priority)
{
    $normalizedPath = "/" . ltrim($path, "/");

    $urls[] = [
        "loc" => $baseUrl . $normalizedPath,
        "lastmod" => $lastmod,
        "changefreq" => $changefreq,
        "priority" => $priority,
    ];
}

$siteUrl = get_site_url($pdo);
$today = date("Y-m-d");
$urls = [];

add_url($urls, $siteUrl, "/", $today, "weekly", "1.0");
add_url($urls, $siteUrl, "/noticias", $today, "weekly", "0.8");

if (table_exists($pdo, "pages")) {
    $columns = get_table_columns($pdo, "pages");
    $selectColumns = ["slug", "created_at", "updated_at"];

    if (isset($columns["page_type"])) {
        $selectColumns[] = "page_type";
    }

    if (isset($columns["published_at"])) {
        $selectColumns[] = "published_at";
    }

    if (isset($columns["noindex"])) {
        $selectColumns[] = "noindex";
    }

    $where = ["is_active = 1"];

    if (isset($columns["noindex"])) {
        $where[] = "(noindex IS NULL OR noindex = 0)";
    }

    if (isset($columns["published_at"])) {
        $where[] = "(published_at IS NULL OR published_at <= NOW())";
    }

    try {
        $stmt = $pdo->query("
            SELECT " . implode(", ", $selectColumns) . "
            FROM pages
            WHERE " . implode(" AND ", $where) . "
            ORDER BY updated_at DESC
        ");

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $page) {
            $pageType = $page["page_type"] ?? "page";
            $lastmod = format_lastmod($page["updated_at"] ?? null)
                ?? format_lastmod($page["created_at"] ?? null);
            $isRegularPage = $pageType === "page";

            add_url(
                $urls,
                $siteUrl,
                "/pagina/" . rawurlencode($page["slug"]),
                $lastmod,
                $isRegularPage ? "monthly" : "weekly",
                $isRegularPage ? "0.8" : "0.7"
            );
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

foreach ($urls as $url) {
    echo "  <url>\n";
    echo "    <loc>" . xml_escape($url["loc"]) . "</loc>\n";

    if ($url["lastmod"]) {
        echo "    <lastmod>" . xml_escape($url["lastmod"]) . "</lastmod>\n";
    }

    echo "    <changefreq>" . xml_escape($url["changefreq"]) . "</changefreq>\n";
    echo "    <priority>" . xml_escape($url["priority"]) . "</priority>\n";
    echo "  </url>\n";
}

echo "</urlset>\n";
