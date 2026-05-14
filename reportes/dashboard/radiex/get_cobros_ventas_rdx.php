<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once '../../../includes/db_connect.php';

$sucursal = (int)($_POST["sucursal"] ?? 1);
$fechaInicio = $_POST["fechaInicio"] ?? null;
$fechaFinal = $_POST["fechaFinal"] ?? null;

if (!$fechaInicio || !$fechaFinal) {
    echo json_encode([
        "data" => [],
        "totalContado" => 0,
        "totalCredito" => 0,
        "totalGeneral" => 0
    ]);
    exit;
}

$map = [
    1 => 'central',
    2 => 'peten',
    3 => 'xela'
];

if (!isset($map[$sucursal])) {
    echo json_encode([
        "data" => [],
        "totalContado" => 0,
        "totalCredito" => 0,
        "totalGeneral" => 0,
        "error" => "Sucursal inválida"
    ]);
    exit;
}

$conn = connectToDatabase($map[$sucursal]);
$conn->set_charset("utf8mb4");

/*
=========================================================
VENTAS RADIEX POR VENDEDOR
Reglas:
- Si venta viene de pedido -> vendedor desde pedido_producto
- Si venta es mostrador (idpedido=0) -> vendedor desde adm_usuario
- Todo abono cubre primero Radiex
- Recuperación parcial se divide contado/crédito
=========================================================
*/

$sql = "

SELECT

    base.vendedor,

    ROUND(
        SUM(base.total_contado),
        2
    ) total_contado,

    ROUND(
        SUM(base.total_credito),
        2
    ) total_credito,

    ROUND(
        SUM(base.total_rdx),
        2
    ) total_general

FROM
(

    SELECT

        v.idventa,

        COALESCE(
            MAX(e.nombre),
            'SIN VENDEDOR'
        ) vendedor,

        SUM(d.total) total_rdx,

        tot.total_factura,

        IFNULL(sc.saldo, 0) saldo_actual,

        /*
        RECUPERADO RADIEX
        */
        LEAST(
            SUM(d.total),
            GREATEST(
                tot.total_factura - IFNULL(sc.saldo, 0),
                0
            )
        ) total_contado,

        /*
        PENDIENTE RADIEX
        */
        SUM(d.total)
        -
        LEAST(
            SUM(d.total),
            GREATEST(
                tot.total_factura - IFNULL(sc.saldo, 0),
                0
            )
        ) total_credito

    FROM adm_venta v

    JOIN adm_detalle_venta d
        ON d.idventa = v.idventa

    JOIN adm_producto p
        ON p.idproducto = d.idproducto

    LEFT JOIN pedido_producto pp
        ON pp.idpedido = v.idpedido

    LEFT JOIN adm_usuario u
        ON u.idadm_usuario = v.id_usuario

    LEFT JOIN adm_empleado e
        ON e.id_empleado =
            CASE
                WHEN v.idpedido > 0
                THEN pp.id_empleado
                ELSE u.id_empleado
            END

    LEFT JOIN saldoxcobrar sc
        ON sc.idventa = v.idventa

    JOIN
    (
        SELECT
            idventa,
            SUM(total) total_factura
        FROM adm_detalle_venta
        GROUP BY idventa
    ) tot
        ON tot.idventa = v.idventa

    WHERE

        v.estado > 0

        AND v.tipo IN ('F','E')

        AND NOT (
            v.tipo = 'F'
            AND v.id_envio > 0
        )

        AND p.codigormym LIKE '%RDX%'

        AND DATE(v.fecha_registro)
        BETWEEN ?
        AND ?

    GROUP BY
        v.idventa,
        tot.total_factura,
        sc.saldo

) base

GROUP BY
    base.vendedor

ORDER BY
    base.vendedor

";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "data" => [],
        "totalContado" => 0,
        "totalCredito" => 0,
        "totalGeneral" => 0,
        "error" => $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "ss",
    $fechaInicio,
    $fechaFinal
);

$stmt->execute();

$res = $stmt->get_result();

$data = [];

$totalContado = 0;
$totalCredito = 0;
$totalGeneral = 0;

while ($r = $res->fetch_assoc()) {

    $r['total_contado'] = (float)$r['total_contado'];
    $r['total_credito'] = (float)$r['total_credito'];
    $r['total_general'] = (float)$r['total_general'];

    $data[] = $r;

    $totalContado += $r['total_contado'];
    $totalCredito += $r['total_credito'];
    $totalGeneral += $r['total_general'];
}

$stmt->close();
$conn->close();

echo json_encode([
    "data" => $data,
    "totalContado" => round($totalContado, 2),
    "totalCredito" => round($totalCredito, 2),
    "totalGeneral" => round($totalGeneral, 2)
]);