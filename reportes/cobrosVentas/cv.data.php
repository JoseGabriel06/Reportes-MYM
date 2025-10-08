<?php
declare(strict_types=1);

// Siempre JSON
header('Content-Type: application/json; charset=utf-8');

// No imprimir notices/warnings en HTML
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Forzar mysqli a lanzar excepciones (en vez de emitir HTML)
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

// Validaciones básicas
if ($fechaInicio === null || $fechaFinal === null || $sucursalId === null) {
    json_fail(400, 'BAD_REQUEST', 'Parámetros requeridos: fechaInicio, fechaFinal, sucursal.');
}

if (!is_string($fechaInicio) || !is_string($fechaFinal) || !valid_date($fechaInicio) || !valid_date($fechaFinal)) {
    json_fail(400, 'INVALID_DATE', 'Formato de fecha inválido. Usa YYYY-MM-DD.', [
        'fechaInicio' => $fechaInicio,
        'fechaFinal'  => $fechaFinal,
    ]);
}

$sucursalId = (int)$sucursalId;
if (!in_array($sucursalId, [1, 2, 3], true)) {
    json_fail(400, 'INVALID_BRANCH', 'Sucursal inválida. Usa 1 (central), 2 (peten), 3 (xela).', [
        'sucursal' => $sucursalId
    ]);
}

// Mapear sucursal a nombre de conexión y schema
$map = [
    1 => ['conn' => 'central', 'schema' => 'db_rmym'],
    2 => ['conn' => 'peten',   'schema' => 'db_rmympt'],
    3 => ['conn' => 'xela',    'schema' => 'db_rmymxela'],
];

$conf = $map[$sucursalId];

// ---------- Conexión ----------
try {
    $conexion = connectToDatabase($conf['conn']); // Debe devolver mysqli conectado al DB correcto
    if (!$conexion instanceof mysqli) {
        json_fail(500, 'CONNECT_RETURN', 'connectToDatabase no retornó una conexión válida.', ['conexion_tipo' => gettype($conexion)]);
    }
    $conexion->set_charset('utf8mb4');
} catch (Throwable $e) {
    json_fail(500, 'DB_CONNECT_ERROR', 'No se pudo abrir la conexión a la base de datos.', [
        'exception' => get_class($e),
        'message'   => $e->getMessage(),
    ]);
}

// ---------- Consulta 1: Cobros por vendedor/semana ----------
$schema = $conexion->real_escape_string($conf['schema']); // solo por seguridad al interpolar nombre de schema

$sqlCobros = "
    SELECT
        r.nombre_vendedor,
        r.semana,
        SUM(r.monto_facturas) AS total_cobro
    FROM adm_recibo AS r
    JOIN {$schema}.clientes              AS cl ON r.idcliente     = cl.idcliente
    JOIN {$schema}.adm_departamentopais  AS dp ON cl.iddepartamento = dp.iddepartamento
    JOIN {$schema}.adm_municipio         AS m  ON cl.id_municipio = m.id_municipio
    WHERE r.estado > 0
      AND DATE(r.fecha_recibo) >= ?
      AND DATE(r.fecha_recibo) <= ?
    GROUP BY r.nombre_vendedor, r.semana
    ORDER BY r.nombre_vendedor
";

$rows = []; // acumulador final (por vendedor)
$diag = ['cobros_count' => 0, 'ventas_count' => 0];

try {
    $stmt = $conexion->prepare($sqlCobros);
    $stmt->bind_param('ss', $fechaInicio, $fechaFinal);
    $stmt->execute();
    $res = $stmt->get_result();

    // Mapa por nombre_vendedor para mezclar luego ventas
    $bySeller = [];
    while ($row = $res->fetch_assoc()) {
        $key = $row['nombre_vendedor'];
        $bySeller[$key] = [
            'nombre_vendedor' => $row['nombre_vendedor'],
            'semana'          => $row['semana'],
            'total_cobro'     => (float)$row['total_cobro'],
            'total_venta'     => 0.0,
        ];
        $diag['cobros_count']++;
    }
    $res->close();
    $stmt->close();
} catch (Throwable $e) {
    json_fail(500, 'SQL_COBROS_ERROR', 'Fallo al obtener cobros.', [
        'exception' => get_class($e),
        'message'   => $e->getMessage(),
        'sql'       => 'sqlCobros',
    ]);
}

// ---------- Consulta 2: Ventas por vendedor ----------
$sqlVentas = "
    SELECT
        e.nombre AS nombre_vendedor,
        SUM(v.total) AS total_venta
    FROM adm_venta v
    JOIN pedido_producto p ON v.idpedido = p.idpedido
    JOIN adm_empleado e    ON p.id_empleado = e.id_empleado
    WHERE v.estado = 1
      AND v.tipo IN ('E','F')
      AND v.id_envio = 0
      AND DATE(v.fecha_registro) >= ?
      AND DATE(v.fecha_registro) <= ?
    GROUP BY e.nombre
    ORDER BY e.nombre
";

try {
    $stmt = $conexion->prepare($sqlVentas);
    $stmt->bind_param('ss', $fechaInicio, $fechaFinal);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $seller = $row['nombre_vendedor'];
        $venta  = (float)$row['total_venta'];

        if (isset($bySeller[$seller])) {
            $bySeller[$seller]['total_venta'] = $venta;
        } else {
            // Si hay ventas de un vendedor que no cobró en el rango, lo agregamos igual
            $bySeller[$seller] = [
                'nombre_vendedor' => $seller,
                'semana'          => null,
                'total_cobro'     => 0.0,
                'total_venta'     => $venta,
            ];
        }
        $diag['ventas_count']++;
    }
    $res->close();
    $stmt->close();
} catch (Throwable $e) {
    json_fail(500, 'SQL_VENTAS_ERROR', 'Fallo al obtener ventas.', [
        'exception' => get_class($e),
        'message'   => $e->getMessage(),
        'sql'       => 'sqlVentas',
    ]);
}

// ---------- Salida ----------
$conexion->close();

// Normalizamos a lista
$rows = array_values($bySeller);

// Entregamos éxito con un pequeño bloque de diagnóstico
json_ok([
    'sucursal' => $sucursalId,
    'rango'    => ['desde' => $fechaInicio, 'hasta' => $fechaFinal],
    'rows'     => $rows,
    'diagnostics' => $diag
]);
