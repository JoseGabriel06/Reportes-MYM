<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db_connect.php';

$fechaInicio = $_POST['fechaInicio'] ?? null;
$fechaFinal  = $_POST['fechaFinal'] ?? null;
$sucursalId  = (int)($_POST['sucursal'] ?? 0);

if (!$fechaInicio || !$fechaFinal) {
    echo json_encode(['ok' => false, 'error' => 'Fechas requeridas']);
    exit;
}

$map = [
    1 => 'central',
    2 => 'peten',
    3 => 'xela',
];

/* ============================================================
   FUNCIÓN QUE GENERA RESUMEN RADIEX
   Regla gerencial:
   - El abono del cliente se aplica primero a Radiex.
   - Si lo abonado cubre Radiex, se considera contado/recuperado.
   - Si no lo cubre completo, solo queda crédito la diferencia.
============================================================ */
function generarResumen($conn, $fechaInicio, $fechaFinal)
{
    $delete = $conn->prepare("
        DELETE FROM resumen_rdx_dashboard
        WHERE fecha_desde = ?
        AND fecha_hasta = ?
    ");
    $delete->bind_param("ss", $fechaInicio, $fechaFinal);
    $delete->execute();
    $delete->close();

    $sql = "

SELECT
    p.idproducto,
    p.codigormym,
    p.nombre,
    IFNULL(pp.costo, 0) costo,

    SUM(t.cantidad_rdx) cantidad,
    SUM(t.total_rdx) total_general,
    SUM(t.total_contado) total_contado,
    SUM(t.total_credito) total_credito

FROM (

    SELECT

        v.idventa,
        d.idproducto,

        SUM(d.cantidad) cantidad_rdx,
        SUM(d.total) total_rdx,

        ROUND(
            SUM(d.total) *
            (
                LEAST(
                    tot.total_rdx,
                    GREATEST(
                        tot.total_factura - IFNULL(sc.saldo, 0),
                        0
                    )
                ) / NULLIF(tot.total_rdx, 0)
            ),
            2
        ) total_contado,

        ROUND(
            SUM(d.total) -
            (
                SUM(d.total) *
                (
                    LEAST(
                        tot.total_rdx,
                        GREATEST(
                            tot.total_factura - IFNULL(sc.saldo, 0),
                            0
                        )
                    ) / NULLIF(tot.total_rdx, 0)
                )
            ),
            2
        ) total_credito

    FROM adm_venta v

    JOIN adm_detalle_venta d
        ON d.idventa = v.idventa

    JOIN adm_producto p
        ON p.idproducto = d.idproducto

    JOIN (
        SELECT
            d2.idventa,
            SUM(d2.total) total_factura,
            SUM(
                CASE
                    WHEN p2.codigormym LIKE '%RDX%'
                    THEN d2.total
                    ELSE 0
                END
            ) total_rdx
        FROM adm_detalle_venta d2
        JOIN adm_producto p2
            ON p2.idproducto = d2.idproducto
        GROUP BY d2.idventa
    ) tot
        ON tot.idventa = v.idventa

    LEFT JOIN saldoxcobrar sc
        ON sc.idventa = v.idventa

    WHERE
        v.estado > 0
        AND v.tipo IN('F','E')
        AND NOT (
            v.tipo = 'F'
            AND v.id_envio > 0
        )
        AND p.codigormym LIKE '%RDX%'
        AND tot.total_rdx > 0
        AND DATE(v.fecha_registro) BETWEEN ? AND ?

    GROUP BY
        v.idventa,
        d.idproducto,
        tot.total_factura,
        tot.total_rdx,
        sc.saldo

) t

JOIN adm_producto p
    ON p.idproducto = t.idproducto

LEFT JOIN (
    SELECT
        idproducto,
        MAX(costo) costo
    FROM precio_producto
    GROUP BY idproducto
) pp
    ON pp.idproducto = p.idproducto

GROUP BY
    p.idproducto,
    p.codigormym,
    p.nombre,
    pp.costo

";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fechaInicio, $fechaFinal);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {

        $insert = $conn->prepare("
            INSERT INTO resumen_rdx_dashboard
            (
                fecha_desde,
                fecha_hasta,
                idproducto,
                codigormym,
                nombre,
                costo,
                cantidad,
                total_general,
                total_contado,
                total_credito
            )
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ");

        $insert->bind_param(
            "ssissddddd",
            $fechaInicio,
            $fechaFinal,
            $r['idproducto'],
            $r['codigormym'],
            $r['nombre'],
            $r['costo'],
            $r['cantidad'],
            $r['total_general'],
            $r['total_contado'],
            $r['total_credito']
        );

        $insert->execute();
        $insert->close();
    }

    $stmt->close();
}

/* ============================================================
   OBTENER TOTALES
============================================================ */
function obtenerTotales($conn, $fechaInicio, $fechaFinal)
{
    $stmt = $conn->prepare("
        SELECT 
            IFNULL(SUM(total_general), 0) total,
            IFNULL(SUM(total_contado), 0) contado,
            IFNULL(SUM(total_credito), 0) credito
        FROM resumen_rdx_dashboard
        WHERE fecha_desde = ?
        AND fecha_hasta = ?
    ");

    $stmt->bind_param("ss", $fechaInicio, $fechaFinal);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return [
        'total' => (float)$res['total'],
        'contado' => (float)$res['contado'],
        'credito' => (float)$res['credito']
    ];
}

/* ============================================================
   MES ANTERIOR
============================================================ */
function obtenerMesAnterior($conn, $fechaInicio, $fechaFinal)
{
    $inicioAnterior = date("Y-m-d", strtotime("-1 month", strtotime($fechaInicio)));
    $finAnterior    = date("Y-m-d", strtotime("-1 month", strtotime($fechaFinal)));

    $stmt = $conn->prepare("
        SELECT IFNULL(SUM(total_general), 0) total
        FROM resumen_rdx_dashboard
        WHERE fecha_desde = ?
        AND fecha_hasta = ?
    ");

    $stmt->bind_param("ss", $inicioAnterior, $finAnterior);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (float)$res['total'];
}

/* ============================================================
   FUNCIÓN PROYECCIÓN INTELIGENTE
============================================================ */
function calcularProyeccion($fechaInicio, $fechaFinal, $total)
{
    $hoy = date("Y-m-d");

    $inicioMesActual = date("Y-m-01");
    $finMesActual = date("Y-m-t");

    $esMesActual =
        ($fechaInicio === $inicioMesActual) &&
        ($fechaFinal === $finMesActual);

    if (!$esMesActual) {
        return [
            'proyeccion' => $total,
            'activa' => false
        ];
    }

    if ($hoy >= $finMesActual) {
        return [
            'proyeccion' => $total,
            'activa' => false
        ];
    }

    $diasTranscurridos = (strtotime($hoy) - strtotime($fechaInicio)) / 86400 + 1;
    $diasMes = date("t");

    if ($diasTranscurridos <= 0) {
        return [
            'proyeccion' => $total,
            'activa' => false
        ];
    }

    $promedioDiario = $total / $diasTranscurridos;
    $proyeccion = $promedioDiario * $diasMes;

    return [
        'proyeccion' => $proyeccion,
        'activa' => true
    ];
}

/* ============================================================
   CONSOLIDADO
============================================================ */
if ($sucursalId === 0) {

    $totales = [];
    $totalEmpresa = 0;
    $totalContado = 0;
    $totalCredito = 0;
    $totalMesAnteriorEmpresa = 0;

    foreach ($map as $connKey) {

        $conn = connectToDatabase($connKey);
        $conn->set_charset("utf8mb4");

        generarResumen($conn, $fechaInicio, $fechaFinal);

        $datos = obtenerTotales($conn, $fechaInicio, $fechaFinal);
        $mesAnterior = obtenerMesAnterior($conn, $fechaInicio, $fechaFinal);

        $totales[$connKey] = $datos['total'];

        $totalEmpresa += $datos['total'];
        $totalContado += $datos['contado'];
        $totalCredito += $datos['credito'];
        $totalMesAnteriorEmpresa += $mesAnterior;

        $conn->close();
    }

    $porcentajeContado = $totalEmpresa > 0 ? ($totalContado / $totalEmpresa) * 100 : 0;
    $porcentajeCredito = $totalEmpresa > 0 ? ($totalCredito / $totalEmpresa) * 100 : 0;

    $variacion = $totalMesAnteriorEmpresa > 0 ?
        (($totalEmpresa - $totalMesAnteriorEmpresa) / $totalMesAnteriorEmpresa) * 100 : 0;

    $proy = calcularProyeccion($fechaInicio, $fechaFinal, $totalEmpresa);

    echo json_encode([
        'ok' => true,
        'modo' => 'consolidado',
        'totales' => $totales,
        'total_empresa' => round($totalEmpresa, 2),
        'contado' => round($totalContado, 2),
        'credito' => round($totalCredito, 2),
        'porcentaje_contado' => round($porcentajeContado, 2),
        'porcentaje_credito' => round($porcentajeCredito, 2),
        'mes_anterior' => round($totalMesAnteriorEmpresa, 2),
        'variacion_porcentual' => round($variacion, 2),
        'proyeccion_mensual' => round($proy['proyeccion'], 2),
        'es_proyeccion_activa' => $proy['activa']
    ]);
    exit;
}

/* ============================================================
   INDIVIDUAL
============================================================ */
if (!isset($map[$sucursalId])) {
    echo json_encode(['ok' => false, 'error' => 'Sucursal inválida']);
    exit;
}

$conn = connectToDatabase($map[$sucursalId]);
$conn->set_charset("utf8mb4");

generarResumen($conn, $fechaInicio, $fechaFinal);

$datos = obtenerTotales($conn, $fechaInicio, $fechaFinal);
$totalMesAnterior = obtenerMesAnterior($conn, $fechaInicio, $fechaFinal);

$stmt = $conn->prepare("
    SELECT *
    FROM resumen_rdx_dashboard
    WHERE fecha_desde = ?
    AND fecha_hasta = ?
");

$stmt->bind_param("ss", $fechaInicio, $fechaFinal);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($r = $res->fetch_assoc()) {
    $data[] = $r;
}
$stmt->close();

$total = $datos['total'];
$totalContado = $datos['contado'];
$totalCredito = $datos['credito'];

$porcentajeContado = $total > 0 ? ($totalContado / $total) * 100 : 0;
$porcentajeCredito = $total > 0 ? ($totalCredito / $total) * 100 : 0;

$variacion = $totalMesAnterior > 0 ?
    (($total - $totalMesAnterior) / $totalMesAnterior) * 100 : 0;

$proy = calcularProyeccion($fechaInicio, $fechaFinal, $total);

$conn->close();

echo json_encode([
    'ok' => true,
    'modo' => 'individual',
    'productos' => $data,
    'total' => round($total, 2),
    'contado' => round($totalContado, 2),
    'credito' => round($totalCredito, 2),
    'porcentaje_contado' => round($porcentajeContado, 2),
    'porcentaje_credito' => round($porcentajeCredito, 2),
    'mes_anterior' => round($totalMesAnterior, 2),
    'variacion_porcentual' => round($variacion, 2),
    'proyeccion_mensual' => round($proy['proyeccion'], 2),
    'es_proyeccion_activa' => $proy['activa']
]);
