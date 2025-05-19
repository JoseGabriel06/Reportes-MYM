<?php
require_once __DIR__ . '/../config/database.php';

function connectToDatabase($dbKey = 'central') {
    $config = getDatabaseConfig($dbKey);

    if (!$config) {
        die("Configuración de base de datos no encontrada para '$dbKey'");
    }

    $conn = new mysqli(
        $config->host,
        $config->username,
        $config->password,
        $config->database,
        $config->port
    );

    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    return $conn;
}
