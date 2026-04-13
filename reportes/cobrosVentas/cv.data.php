<?php
declare(strict_types=1);

// Siempre JSON
header('Content-Type: application/json; charset=utf-8');

// No imprimir notices/warnings en HTML
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Forzar mysqli a lanzar excepciones
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../../includes/db_connect.php';

// ---------- Helpers ----------
function json_fail(int $http_code, string $error_code, string $message, array $context = []): void {
    http_response_code($http_code);
    echo json_encode([
        'ok' => false,
        'error_code' => $error_code,
        'error_message' => $message,
        'context' => $context,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_ok(array $payload): void {
    echo json_encode(array_merge(['ok' => true], $payload), JSON_UNESCAPED_UNICODE);
    exit;
}

function valid_date(string $d): bool {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

// ---------- Entradas ----------
$fechaInicio = $_POST['fechaInicio'] ?? null;
$fechaFinal  = $_POST['fechaFinal']  ?? null;
$sucursalId  = $_POST['sucursal']    ?? null;

// Validaciones
if ($fechaInicio === null || $fechaFinal === null || $sucursalId === null) {
    json_fail(400, 'BAD_REQUEST', 'Parámetros requeridos: fechaInicio, fechaFinal, sucursal.');
}

if (!valid_date($fechaInicio) || !valid_date($fechaFinal)) {
    json_fail(400, 'INVALID_DATE', 'Formato de fecha inválido.');
}

$sucursalId = (int)$sucursalId;

$map = [
    1 => ['conn' => 'central', 'schema' => 'db_rmym'],
    2 => ['conn' => 'peten',   'schema' => 'db_rmympt'],
    3 => ['conn' => 'xela',    'schema' => 'db_rmymxela'],
];

$conf = $map[$sucursalId];

// ---------- Conexión ----------
$conexion = connectToDatabase($conf['conn']);
$conexion->set_charset('utf8mb4');

$schema = $conexion->real_escape_string($conf['schema']);


// =====================================================
// 🔥 CONSULTA COBROS (100% IGUAL A C#)
// =====================================================

$sqlCobros = "
    SELECT
        e.nombre AS nombre_vendedor,
        SUM(r.cobro) AS total_cobro
    FROM {$schema}.vnt_registro_recibo r
";

// 🔁 JOIN SEGÚN SUCURSAL (CRÍTICO)
if ($sucursalId == 2) { // PETÉN
    $sqlCobros .= "
        JOIN {$schema}.adm_usuario u 
            ON r.id_usuario = u.usuario_app
        JOIN {$schema}.adm_empleado e 
            ON u.id_empleado = e.id_empleado
        JOIN {$schema}.clientes c 
            ON r.id_cliente = c.idcliente
    ";
} else { // CENTRAL Y XELA
    $sqlCobros .= "
        JOIN {$schema}.adm_usuario u 
            ON r.id_usuario = u.idadm_usuario
        JOIN {$schema}.adm_empleado e 
            ON u.id_empleado = e.id_empleado
            AND e.estado > 0
        JOIN {$schema}.clientes c 
            ON r.id_cliente = c.idcliente
    ";
}

// 🔥 IMPORTANTE: USAR DATE EXACTO COMO C#
$sqlCobros .= "
    WHERE r.estado > 0
    AND DATE(r.fecha_recibo) >= ?
    AND DATE(r.fecha_recibo) <= ?
    GROUP BY e.nombre
    ORDER BY e.nombre
";

$rows = [];
$bySeller = [];

// ---------- Ejecutar cobros ----------
$stmt = $conexion->prepare($sqlCobros);
$stmt->bind_param('ss', $fechaInicio, $fechaFinal);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $key = trim($row['nombre_vendedor']);

    $bySeller[$key] = [
        'nombre_vendedor' => $key,
        'semana'          => $row['semana'],
        'total_cobro'     => (float)$row['total_cobro'],
        'total_venta'     => 0.0,
    ];
}

$res->close();
$stmt->close();


// =====================================================
// 🔥 CONSULTA VENTAS (SIN CAMBIOS)
// =====================================================

$sqlVentas = "
    SELECT
        e.nombre AS nombre_vendedor,
        SUM(v.total) AS total_venta
    FROM adm_venta v
    JOIN pedido_producto p ON v.idpedido = p.idpedido
    JOIN adm_empleado e ON p.id_empleado = e.id_empleado
    WHERE v.estado = 1
      AND v.tipo IN ('E','F')
      AND v.id_envio = 0
      AND DATE(v.fecha_registro) >= ?
      AND DATE(v.fecha_registro) <= ?
    GROUP BY e.nombre
";

$stmt = $conexion->prepare($sqlVentas);
$stmt->bind_param('ss', $fechaInicio, $fechaFinal);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $seller = trim($row['nombre_vendedor']);
    $venta  = (float)$row['total_venta'];

    if (isset($bySeller[$seller])) {
        $bySeller[$seller]['total_venta'] = $venta;
    } else {
        $bySeller[$seller] = [
            'nombre_vendedor' => $seller,
            'semana'          => null,
            'total_cobro'     => 0.0,
            'total_venta'     => $venta,
        ];
    }
}

$res->close();
$stmt->close();

$conexion->close();

$rows = array_values($bySeller);

// ---------- Salida ----------
json_ok([
    'rows' => $rows
]);