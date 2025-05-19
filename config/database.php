<?php
function getDatabaseConfig($dbKey = 'central') {
    $configs = include realpath(__DIR__ . '/../.env.php'); // ajusta la ruta si es necesario
    return isset($configs[$dbKey]) ? (object)$configs[$dbKey] : null;
}
