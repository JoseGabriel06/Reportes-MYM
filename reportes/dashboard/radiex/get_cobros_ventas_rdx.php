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

$sql = "

SELECT

base.vendedor,

SUM(
CASE
WHEN base.es_contado = 1
THEN base.total_venta
ELSE 0
END
) total_contado,

SUM(
CASE
WHEN base.es_credito = 1
THEN base.total_venta
ELSE 0
END
) total_credito,

SUM(base.total_venta) total_general

FROM
(

SELECT

v.idventa,

COALESCE(
MAX(e.nombre),
'SIN VENDEDOR'
) vendedor,

SUM(d.total) total_venta,

CASE
WHEN
IFNULL(sc.saldo,0)=0
AND fr.primer_recibo IS NOT NULL
AND DATEDIFF(
fr.primer_recibo,
v.fecha_registro
)<=7
THEN 1
ELSE 0
END es_contado,

CASE
WHEN
IFNULL(sc.saldo,0)>0
OR fr.primer_recibo IS NULL
OR DATEDIFF(
fr.primer_recibo,
v.fecha_registro
)>7
THEN 1
ELSE 0
END es_credito

FROM adm_venta v

JOIN adm_detalle_venta d
ON d.idventa = v.idventa

JOIN adm_producto p
ON p.idproducto = d.idproducto

LEFT JOIN pedido_producto pp
ON pp.idpedido = v.idpedido

LEFT JOIN adm_empleado e
ON e.id_empleado = pp.id_empleado

LEFT JOIN saldoxcobrar sc
ON sc.idventa = v.idventa

LEFT JOIN
(
SELECT
fr.idventa,
MIN(r.fecha_recibo) primer_recibo
FROM adm_facturas_recibo fr
JOIN adm_recibo r
ON r.idrecibo = fr.idrecibo
GROUP BY fr.idventa
) fr
ON fr.idventa = v.idventa

WHERE

v.estado > 0

AND v.tipo IN('F','E')

AND NOT(
v.tipo = 'F'
AND v.id_envio > 0
)

AND p.codigormym LIKE '%RDX%'

AND DATE(v.fecha_registro)
BETWEEN ?
AND ?

GROUP BY
v.idventa,
v.fecha_registro,
sc.saldo,
fr.primer_recibo

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
