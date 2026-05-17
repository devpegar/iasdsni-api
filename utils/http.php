<?php

function json_success($data = null, $message = null, $status = 200)
{
    http_response_code($status);

    $response = ["success" => true];

    if (is_array($data)) {
        $response = array_merge($response, $data);
    } elseif ($data !== null) {
        $response["data"] = $data;
    }

    if ($message !== null) {
        $response["message"] = $message;
    }

    echo json_encode($response);
    exit;
}

function json_error($message = "Error interno del servidor", $status = 500, $errors = null)
{
    http_response_code($status);

    $response = [
        "success" => false,
        "message" => $message,
    ];

    if ($errors !== null) {
        $response["errors"] = $errors;
    }

    echo json_encode($response);
    exit;
}

function read_json_body()
{
    $rawBody = file_get_contents("php://input");

    if ($rawBody === false || trim($rawBody) === "") {
        return [];
    }

    $data = json_decode($rawBody, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        json_error("JSON inválido", 400);
    }

    return $data;
}

function require_method($method)
{
    $expectedMethod = strtoupper($method);
    $requestMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";

    if ($requestMethod !== $expectedMethod) {
        header("Allow: {$expectedMethod}");
        json_error("Método no permitido", 405);
    }
}
