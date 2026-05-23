<?php

function normalize_gallery_slug($value)
{
    $value = strtolower(trim((string)$value));

    if (function_exists("iconv")) {
        $converted = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = preg_replace("/[^a-z0-9]+/", "-", $value);
    return trim($value, "-");
}

function nullable_trim($value)
{
    $value = trim((string)$value);
    return $value !== "" ? $value : null;
}

function normalize_date_or_null($value)
{
    $value = trim((string)$value);
    return preg_match("/^\d{4}-\d{2}-\d{2}$/", $value) ? $value : null;
}
