<?php
require_once('../../connection.php');

    $departamento = $_POST['departamento'];

    if ($mysqli->connect_error) {
        die("Error de conexión: " . $mysqli->connect_error);
    }

    // Consulta para obtener municipios por departamento
    $stmt = $mysqli->prepare("SELECT nombre FROM db_rmym.adm_municipio WHERE id_departamento = (SELECT iddepartamento FROM db_rmym.adm_departamentopais WHERE nombre = ?)");
    $stmt->bind_param('s', $departamento);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $opciones = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $opciones[] = $fila["nombre"];
        }
    }



$mysqli->close();
    echo json_encode($opciones);
?>