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

// Validar la conexión
if ($mysqli->connect_error) {
    http_response_code(500); // Código HTTP 500 para error del servidor
    echo json_encode(["error" => "Error de conexión: " . $mysqli->connect_error]);
    exit;
}

// Consulta SQL
$consulta = "SELECT e.nombre as vendedor,sum(s.saldo) as saldo
from saldoxcobrar s
join adm_venta v on s.idventa = v.idventa 
join pedido_producto p on v.idpedido = p.idpedido
join adm_empleado e on p.id_empleado = e.id_empleado
where s.estado > 0 and s.saldo > 0
group by vendedor;";

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
    $datos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $datos[] = $fila; // Añadir cada fila al arreglo de datos
    }

    if (!empty($datos)) {
        // Devolver todos los registros en formato JSON
        echo json_encode($datos);
    } else {
        // No se encontraron registros
        echo json_encode(["error" => "No se encontraron resultados."]);
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
