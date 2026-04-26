<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once '../../../includes/db_connect.php';


function salir($arr)
{
    echo json_encode(
        $arr,
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

try {

    $fechaInicio =
        $_POST['fechaInicio'] ?? '';

    $fechaFinal =
        $_POST['fechaFinal'] ?? '';

    $sucursal =
        (int)($_POST['sucursal'] ?? 1);


    $map = [
        1 => 'central',
        2 => 'peten',
        3 => 'xela'
    ];

    if (!isset($map[$sucursal])) {
        salir([
            "rows" => [],
            "totalCobrado" => 0,
            "debug" => "Sucursal inválida"
        ]);
    }


    $conn =
        connectToDatabase(
            $map[$sucursal]
        );

    $conn->set_charset(
        "utf8mb4"
    );


    /*
==================================================
SE EVITA DUPLICAR COBROS:
1 fila por recibo + venta
luego consolidado por vendedor
==================================================
*/

    $sql = "

SELECT

base.vendedor,

SUM(base.cobro_rdx) total_cobrado,

COUNT(DISTINCT base.idrecibo) recibos,

COUNT(DISTINCT base.idventa) ventas_cobradas

FROM (

SELECT DISTINCT

r.idrecibo,
fr.idventa,

COALESCE(
e.nombre,
'SIN VENDEDOR'
) vendedor,

r.monto_facturas,

tot.total_venta,

rdx.total_rdx,

(
r.monto_facturas *
(
rdx.total_rdx /
NULLIF(tot.total_venta,0)
)
) cobro_rdx

FROM adm_recibo r

JOIN adm_facturas_recibo fr
ON fr.idrecibo=r.idrecibo

JOIN adm_venta v
ON v.idventa=fr.idventa

LEFT JOIN pedido_producto pp
ON pp.idpedido=v.idpedido

LEFT JOIN adm_empleado e
ON e.id_empleado=pp.id_empleado


JOIN(
SELECT
idventa,
SUM(total) total_venta
FROM adm_detalle_venta
GROUP BY idventa
) tot
ON tot.idventa=v.idventa


JOIN(
SELECT
d.idventa,
SUM(d.total) total_rdx
FROM adm_detalle_venta d
JOIN adm_producto p
ON p.idproducto=d.idproducto
WHERE p.codigormym LIKE '%RDX%'
GROUP BY d.idventa
) rdx
ON rdx.idventa=v.idventa


WHERE
r.estado>0

AND DATE(r.fecha_recibo)
BETWEEN ?
AND ?

) base

GROUP BY base.vendedor
ORDER BY base.vendedor

";


    $stmt =
        $conn->prepare(
            $sql
        );

    $stmt->bind_param(
        "ss",
        $fechaInicio,
        $fechaFinal
    );

    $stmt->execute();

    $res =
        $stmt->get_result();


    $data = [];

    $totalCobrado = 0;


    while (
        $r = $res->fetch_assoc()
    ) {

        $r["total_cobrado"] =
            (float)$r["total_cobrado"];

        $r["recibos"] =
            (int)$r["recibos"];

        $r["ventas_cobradas"] =
            (int)$r["ventas_cobradas"];


        $data[] = $r;

        $totalCobrado +=
            $r["total_cobrado"];
    }


    $stmt->close();
    $conn->close();


    salir([

        "data" => $data,

        "rows" => $data,

        "totalCobrado" => round(
            $totalCobrado,
            2
        )

    ]);
} catch (Throwable $e) {

    salir([
        "data" => [],
        "rows" => [],
        "totalCobrado" => 0,
        "debug" => $e->getMessage()
    ]);
}
