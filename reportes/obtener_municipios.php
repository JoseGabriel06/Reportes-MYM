<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departamento = $_POST['departamentossdadsfasdf'];

    $servidor = 'localhostsdsdf';
    $usuario = 'root';
    $contrasena = 'MyG4b0QL2023**@##';
    $baseDeDatos = 'db_mymsa';

    $conexion = new mysqli($servidor, $usuario, $contrasena, $baseDeDatos);

    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    // Consulta para obtener municipios por departamento
    $stmt = $conexion->prepare("SELECT nombre FROM db_rmym.adm_municipio WHERE id_departamento = (SELECT iddepartamento FROM db_rmym.adm_departamentopais WHERE nombre = ?)");
    $stmt->bind_param('s', $departamento);
    $stmt->execute();
    $result = $stmt->get_result();

    $municipios = "<option value=''>Todos los Municipios</option>";
    while ($fila = $result->fetch_assoc()) {
        $municipios .= "<option value='{$fila['nombre']}'>{$fila['nombre']}</option>";
    }

    $stmt->close();
    $conexion->close();

    echo $municipios;
}
?>