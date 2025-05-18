<?php
// Permitir solicitudes desde el origen del frontend
header('Access-Control-Allow-Origin: http://localhost:5178'); // Cambiar al origen de tu frontend
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true'); // Si necesitas enviar cookies o credenciales

// Manejo de preflight request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); // Responder con éxito a las solicitudes OPTIONS
    exit;
}

// Tipo de contenido esperado
header('Content-Type: application/json');

// Conexión a la base de datos
require_once '../../../connection.php';

$sucursal = $_GET['sucursal'];
if ($sucursal === 'central') {
    $baseDatos = 'db_mymsa';
}else if($sucursal === 'peten'){
    $baseDatos = 'db_mymsapt';
}else if($sucursal === 'xela'){
    $baseDatos = 'db_mymsaxela';
}
$mysqli->select_db($baseDatos);
// Validar la conexión
if ($mysqli->connect_error) {
    http_response_code(500); // Código HTTP 500 para error del servidor
    echo json_encode(["error" => "Error de conexión: " . $mysqli->connect_error]);
    exit;
}

// Consulta SQL
$consulta = "SELECT SUM(s.saldo) AS saldo 
             FROM saldoxcobrar s 
             WHERE s.estado > 0 AND s.saldo > 0";

$stmt = $mysqli->prepare($consulta);

if (!$stmt) {
    http_response_code(500); // Error interno del servidor
    echo json_encode(["error" => "Error en la consulta SQL: " . $mysqli->error]);
    exit;
}

// Ejecutar la consulta
$stmt->execute();
$resultado = $stmt->get_result();

// Procesar el resultado
if ($resultado) {
    if ($resultado->num_rows > 0) {
        // Convertir los datos en un objeto JSON
        $row = $resultado->fetch_assoc();
        echo json_encode($row); // Devolver directamente el objeto con 'saldo'
    } else {
        // No se encontraron registros
        echo json_encode(["error" => "No se encontró saldo."]);
    }
    $resultado->close();
} else {
    http_response_code(500); // Error interno del servidor
    echo json_encode(["error" => "Error al ejecutar la consulta."]);
}

// Cerrar conexiones
$stmt->close();
$mysqli->close();
?>
