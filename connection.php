<?php
session_start();
$config = require "config.php";

$host = $config->host;
$port = $config->port;
$database = $config->database;
$username = $config->username;
$password = $config->pass;
 /**
  * Se construye la cadena de conexión hacia la base de datos de la sucursal 
  * a la que pertenece el usuario.
  */
$mysqli = new mysqli($host, $username, $password, $database, $port);

if ($mysqli->connect_errno) {
    //TODO: ENVIAR EL RESULTADO DEL ERROR A DISCO O BASE DE DATOS.
    echo "Fallo al conectar a MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    //return $mysqli->connect_errno;

    return null;
} else {
    //return $mysqli->connect_errno;
    // echo "ok connection";
    return $mysqli;
}