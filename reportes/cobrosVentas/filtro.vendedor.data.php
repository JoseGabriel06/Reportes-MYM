<?php
require_once('../../connection.php');
    

    if ($mysqli->connect_error) {
        die("Error de conexión: " . $mysqli->connect_error);
    }

    // Consulta para obtener municipios por departamento
   $consulta = "SELECT e.nombre FROM db_rmym.adm_empleado e join db_rmym.adm_puesto p on e.id_puesto = p.id_puesto WHERE e.estado = 1 and e.id_sucursal = 1 and (p.nombre like '%VENTA%' or p.nombre like '%VENDEDOR%');";
    $resultado = $mysqli->query($consulta);

    $opciones = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $opciones[] = $fila["nombre"];
        }
    }

    $mysqli->close();
    echo json_encode($opciones);
    ?>