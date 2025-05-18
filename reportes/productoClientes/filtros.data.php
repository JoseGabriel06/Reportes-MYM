<?php
require_once('../../connection.php');
// Obtener opciones únicas para filtros
$campo = $_POST["campo"];
$tabla = $_POST["tabla"];

    if ($mysqli->connect_error) {
        die("Error de conexión: " . $mysqli->connect_error);
    }

    $consulta = "SELECT DISTINCT $campo FROM $tabla ORDER BY $campo";
    $resultado = $mysqli->query($consulta);

    $opciones = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $opciones[] = $fila[$campo];
        }
    }

    $mysqli->close();
    echo json_encode($opciones);
    ?>