<?php
 require_once __DIR__ . '/../../includes/db_connect.php';
    $conexion = connectToDatabase('central');

    $departamento = $_POST['departamento'];

    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    // Consulta para obtener municipios por departamento
    $stmt = $conexion->prepare("SELECT nombre FROM adm_municipio WHERE id_departamento = (SELECT iddepartamento FROM adm_departamentopais WHERE nombre = ?)");
    $stmt->bind_param('s', $departamento);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $opciones = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $opciones[] = $fila["nombre"];
        }
    }



$conexion->close();
    echo json_encode($opciones);
?>