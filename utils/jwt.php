<?php

function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data)
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }

    return base64_decode(strtr($data, '-_', '+/'), true);
}

function create_jwt($payload, $secret, $expiration_minutes = 120)
{
    $header = ["alg" => "HS256", "typ" => "JWT"];

    $payload["exp"] = time() + ($expiration_minutes * 60);

    $header_enc = base64url_encode(json_encode($header));
    $payload_enc = base64url_encode(json_encode($payload));

    $signature = hash_hmac("sha256", "$header_enc.$payload_enc", $secret, true);
    $signature_enc = base64url_encode($signature);

    return "$header_enc.$payload_enc.$signature_enc";
}

function jwt_debug_log($message, array $context = [])
{
    error_log('[jwt] ' . $message . ($context ? ' ' . json_encode($context) : ''));
}

function validate_jwt($jwt, $secret)
{
    $parts = explode('.', $jwt);

    if (count($parts) !== 3) {
        jwt_debug_log('invalid token parts', ['parts' => count($parts)]);
        return false;
    }

    list($header_enc, $payload_enc, $signature_enc) = $parts;

    $signature_check = base64url_encode(
        hash_hmac("sha256", "$header_enc.$payload_enc", $secret, true)
    );

    if (!hash_equals($signature_check, $signature_enc)) {
        jwt_debug_log('signature mismatch', [
            'secret_len' => is_string($secret) ? strlen($secret) : 0,
            'token_sha256_12' => substr(hash('sha256', $jwt), 0, 12)
        ]);
        return false;
    }

    $payload = json_decode(base64url_decode($payload_enc), true);

    if (!is_array($payload)) {
        jwt_debug_log('payload decode failed', ['json_error' => json_last_error_msg()]);
        return false;
    }

    if (!isset($payload["exp"]) || !is_numeric($payload["exp"])) {
        jwt_debug_log('missing exp claim');
        return false;
    }

    if ((int)$payload["exp"] < time()) {
        jwt_debug_log('token expired', ['exp' => (int)$payload["exp"], 'now' => time()]);
        return false;
    }

    return $payload;
}
