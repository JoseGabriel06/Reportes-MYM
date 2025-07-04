<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departamento = $_POST['departamento'];

    require_once __DIR__ . '/../../../../includes/db_connect.php';
    $conexion = connectToDatabase('central');

    // $servidor = '181.114.25.86';
    // $usuario = 'usr_mym';
    // $contrasena = 'Mym*20#*81@_)';
    // $port = 3307;
    // $baseDeDatos = 'db_mymsa';

    // $conexion = new mysqli($servidor, $usuario, $contrasena, $baseDeDatos,$port);

    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    // Consulta para obtener municipios por departamento
    $stmt = $conexion->prepare("SELECT nombre FROM adm_municipio WHERE id_departamento = (SELECT iddepartamento FROM adm_departamentopais WHERE nombre = ?)");
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
