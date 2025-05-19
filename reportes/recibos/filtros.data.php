<?php
 require_once __DIR__ . '/../../includes/db_connect.php';
    $conexion = connectToDatabase('central');
$campo = $_POST["campo"];
$tabla = $_POST["tabla"];

    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    $consulta = "SELECT DISTINCT $campo FROM $tabla ORDER BY $campo";
    $resultado = $conexion->query($consulta);

    $opciones = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $opciones[] = $fila[$campo];
        }
    }

    $conexion->close();
    echo json_encode($opciones);
    ?>