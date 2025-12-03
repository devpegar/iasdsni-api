<?php

$host = "localhost";
$dbname = "iasdsni-db";
$username = "root";
$password = ""; // en Laragon no tiene clave

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Error en la conexión: " . $e->getMessage());
}
