<?php

function normalize_belief_slug($slug)
{
    $slug = strtolower(trim((string)$slug));

    if (function_exists("iconv")) {
        $converted = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $slug);
        if ($converted !== false) {
            $slug = $converted;
        }
    }

    $slug = preg_replace("/[^a-z0-9]+/", "-", $slug);
    return trim($slug, "-");
}

function belief_bool($value, $default = 1)
{
    if ($value === null) {
        return $default;
    }

    return (int)$value ? 1 : 0;
}

function map_belief_doctrine($row)
{
    return [
        "id" => (int)$row["id"],
        "title" => $row["title"],
        "slug" => $row["slug"],
        "summary" => $row["summary"],
        "image_url" => $row["image_url"],
        "sort_order" => (int)$row["sort_order"],
        "is_active" => (int)$row["is_active"],
        "created_at" => $row["created_at"],
        "updated_at" => $row["updated_at"],
    ];
}

function map_belief_item($row)
{
    return [
        "id" => (int)$row["id"],
        "doctrine_id" => (int)$row["doctrine_id"],
        "doctrine_title" => $row["doctrine_title"] ?? null,
        "title" => $row["title"],
        "slug" => $row["slug"],
        "content" => $row["content"],
        "bible_references" => $row["bible_references"],
        "sort_order" => (int)$row["sort_order"],
        "is_active" => (int)$row["is_active"],
        "created_at" => $row["created_at"],
        "updated_at" => $row["updated_at"],
    ];
}
