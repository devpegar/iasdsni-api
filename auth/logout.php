<?php
require_once "../utils/cors.php";

// Si en el futuro querés invalidar token en servidor, acá debería hacerse.
// Por ahora, con JWT puro, solo limpiamos cookie (si existiera).

setcookie("token", "", time() - 3600, "/", "", false, true);

echo json_encode([
    "success" => true,
    "message" => "Sesión cerrada"
]);
