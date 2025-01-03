<?php
session_start();
$config = include "config.php";

$host = $config->host;
$port = $config->port;
$database = '';
$username = '';
$password = '';

/**
 * Si existe la variable sucursal, se verifica el valor
 * para crear la cadena de conexión que direccione los registros
 * a la base de datos asignada a la sucursal.
 * 
 * Si no existe la variable sucursal, se establecen los datos
 * de conexión predefinidos, que son a la central.
 * 
 * La sucursal 1 es la central, los datos de conexión son los predefinidos
 * La sucursal 2 es Petén, se cambia el nombre de la base de datos y las credenciales de acceso
 * La sucursal 3 es Xela, solo se cambia la base de datos, ya que las credenciales son las mismas que la central
 */
// if (isset($_SESSION['sucursal'])) {
//     $sucursal = $_SESSION['sucursal'];
//     if (intval($sucursal) == 1) {
//         $database = $config->database;
//         $username = $config->username;
//         $password = $config->pass;
//     } else if (intval($sucursal) == 2) {
//        $database = 'db_rmympt';
//        /*$username = $config->username;
//         $password = $config->pass;*/
//         $username = 'usr_speten';
//         $password = 'P3t3nMym22*#';
//     }
//     else if(intval($sucursal) == 3)
//     {
//         $database = 'db_rmymxela';
//         $username = $config->username;
//         $password = $config->pass;
//     }
// } else {
    $database = $config->database;
    $username = $config->username;
    $password = $config->pass;
// }

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