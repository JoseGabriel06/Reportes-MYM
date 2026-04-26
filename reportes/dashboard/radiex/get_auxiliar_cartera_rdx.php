<?php

header('Content-Type: application/json');

require_once '../../../includes/db_connect.php';

$sucursal = (int)$_POST["sucursal"];

$fechaInicio = $_POST["fechaInicio"];
$fechaFinal = $_POST["fechaFinal"];

$tipoPago = $_POST["tipoPago"];

$iddepto = $_POST["departamento"] ?? '';
$idmuni = $_POST["municipio"] ?? '';

$map = [
    1 => 'central',
    2 => 'peten',
    3 => 'xela'
];

$conn =
    connectToDatabase(
        $map[$sucursal]
    );

$conn->set_charset("utf8mb4");


$clientesDb = [
    1 => 'db_rmym',
    2 => 'db_rmympt',
    3 => 'db_rmymxela'
];

$schemaClientes =
    $clientesDb[$sucursal];


$filtro = '';

if ($iddepto != '') {
    $filtro .= "
AND c.iddepartamento=$iddepto
";
}

if ($idmuni != '') {
    $filtro .= "
AND c.id_municipio=$idmuni
";
}


$having = '';

if ($tipoPago == "CONTADO") {
    $having = "
HAVING SUM(t.pendiente)=0
";
}

if ($tipoPago == "CREDITO") {
    $having = "
HAVING SUM(t.pendiente)>0
";
}



/*=====================================
DETALLE
=====================================*/

$sql = "

SELECT

MIN(t.fecha) fecha,

COUNT(
DISTINCT t.idventa
) documento,

t.cliente,
t.departamento,
t.municipio,

CASE
WHEN SUM(t.pendiente)>0
THEN 'CREDITO'
ELSE 'CONTADO'
END tipo_pago,

CASE
WHEN SUM(t.pendiente)>0
THEN SUM(t.pendiente)
ELSE SUM(t.contado_monto)
END pendiente,

MAX(
t.dias_vencidos
) dias_vencidos,

t.vendedor

FROM(

SELECT

v.idventa,
DATE(v.fecha_registro) fecha,

c.primer_nombre cliente,

dep.nombre departamento,
mun.nombre municipio,

e.nombre vendedor,


CASE
WHEN
(
IFNULL(sc.saldo,0)>0
OR fr.primer_recibo IS NULL
OR DATEDIFF(
fr.primer_recibo,
v.fecha_registro
)>7
)
THEN SUM(d.total)
ELSE 0
END pendiente,


CASE
WHEN
IFNULL(sc.saldo,0)=0
AND fr.primer_recibo IS NOT NULL
AND DATEDIFF(
fr.primer_recibo,
v.fecha_registro
)<=7
THEN SUM(d.total)
ELSE 0
END contado_monto,


CASE
WHEN IFNULL(sc.saldo,0)>0
THEN DATEDIFF(
CURDATE(),
v.fecha_registro
)
ELSE 0
END dias_vencidos


FROM adm_venta v

JOIN adm_detalle_venta d
ON d.idventa=v.idventa

JOIN adm_producto p
ON p.idproducto=d.idproducto

JOIN {$schemaClientes}.clientes c
ON c.idcliente=v.id_cliente

LEFT JOIN {$schemaClientes}.adm_departamentopais dep
ON dep.iddepartamento=c.iddepartamento

LEFT JOIN {$schemaClientes}.adm_municipio mun
ON mun.id_municipio=c.id_municipio

JOIN pedido_producto pp
ON pp.idpedido=v.idpedido

JOIN adm_empleado e
ON e.id_empleado=pp.id_empleado


LEFT JOIN saldoxcobrar sc
ON sc.idventa=v.idventa


LEFT JOIN(
SELECT
fr.idventa,
MIN(r.fecha_recibo) primer_recibo
FROM adm_facturas_recibo fr
JOIN adm_recibo r
ON r.idrecibo=fr.idrecibo
GROUP BY fr.idventa
) fr
ON fr.idventa=v.idventa


WHERE

v.estado>0

AND v.tipo IN('F','E')

AND NOT(
v.tipo='F'
AND v.id_envio>0
)

AND p.codigormym LIKE '%RDX%'

AND DATE(v.fecha_registro)
BETWEEN ?
AND ?

$filtro

GROUP BY

v.idventa,
v.fecha_registro,
c.primer_nombre,
dep.nombre,
mun.nombre,
e.nombre,
sc.saldo,
fr.primer_recibo

)t

GROUP BY
t.cliente,
t.departamento,
t.municipio,
t.vendedor

$having

ORDER BY
t.departamento,
t.municipio,
tipo_pago,
dias_vencidos DESC

";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ss",
    $fechaInicio,
    $fechaFinal
);

$stmt->execute();

$res = $stmt->get_result();

$data = [];
$totalClientes = 0;

while ($r = $res->fetch_assoc()) {
    $data[] = $r;
    $totalClientes++;
}

$stmt->close();



/*=====================================
KPI OFICIAL SEGÚN FILTRO
=====================================*/

$totalSql = "

SELECT

SUM(
CASE

WHEN ?='CONTADO'
AND IFNULL(sc.saldo,0)=0
AND fr.primer_recibo IS NOT NULL
AND DATEDIFF(
fr.primer_recibo,
v.fecha_registro
)<=7
THEN d.total


WHEN ?='CREDITO'
AND (
IFNULL(sc.saldo,0)>0
OR fr.primer_recibo IS NULL
OR DATEDIFF(
fr.primer_recibo,
v.fecha_registro
)>7
)
THEN d.total


WHEN ?='TODOS'
THEN d.total

ELSE 0

END

) total_monto


FROM adm_venta v

JOIN adm_detalle_venta d
ON d.idventa=v.idventa

JOIN adm_producto p
ON p.idproducto=d.idproducto

LEFT JOIN saldoxcobrar sc
ON sc.idventa=v.idventa

LEFT JOIN(
SELECT
fr.idventa,
MIN(r.fecha_recibo) primer_recibo
FROM adm_facturas_recibo fr
JOIN adm_recibo r
ON r.idrecibo=fr.idrecibo
GROUP BY fr.idventa
) fr
ON fr.idventa=v.idventa


WHERE

v.estado>0

AND v.tipo IN('F','E')

AND NOT(
v.tipo='F'
AND v.id_envio>0
)

AND p.codigormym LIKE '%RDX%'

AND DATE(v.fecha_registro)
BETWEEN ?
AND ?

";


$t = $conn->prepare(
    $totalSql
);

$t->bind_param(
    "sssss",
    $tipoPago,
    $tipoPago,
    $tipoPago,
    $fechaInicio,
    $fechaFinal
);

$t->execute();

$row =
    $t->get_result()
    ->fetch_assoc();

$totalPendiente =
    (float)$row["total_monto"];

$t->close();

$conn->close();



echo json_encode([

    'data' => $data,

    'totalPendiente' => round(
        $totalPendiente,
        2
    ),

    'totalClientes' => $totalClientes

]);
