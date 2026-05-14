<?php

header('Content-Type: application/json');

require_once '../../../includes/db_connect.php';

$sucursal = (int)($_POST["sucursal"] ?? 1);

$fechaInicio = $_POST["fechaInicio"] ?? null;
$fechaFinal  = $_POST["fechaFinal"] ?? null;

$tipoPago = $_POST["tipoPago"] ?? 'TODOS';

$iddepto = $_POST["departamento"] ?? '';
$idmuni  = $_POST["municipio"] ?? '';

if (!$fechaInicio || !$fechaFinal) {
    echo json_encode([
        'data' => [],
        'totalPendiente' => 0,
        'totalClientes' => 0
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
        'data' => [],
        'totalPendiente' => 0,
        'totalClientes' => 0,
        'error' => 'Sucursal inválida'
    ]);
    exit;
}

$conn = connectToDatabase($map[$sucursal]);
$conn->set_charset("utf8mb4");

$clientesDb = [
    1 => 'db_rmym',
    2 => 'db_rmympt',
    3 => 'db_rmymxela'
];

$schemaClientes = $clientesDb[$sucursal];

$filtro = '';

if ($iddepto != '') {
    $iddepto = (int)$iddepto;
    $filtro .= "
AND c.iddepartamento = $iddepto
";
}

if ($idmuni != '') {
    $idmuni = (int)$idmuni;
    $filtro .= "
AND c.id_municipio = $idmuni
";
}

$having = '';

if ($tipoPago == 'CONTADO') {
    $having = "
HAVING SUM(t.saldo_rdx) = 0
";
}

if ($tipoPago == 'CREDITO') {
    $having = "
HAVING SUM(t.saldo_rdx) > 0
";
}

/*
=============================================
AUXILIAR DE CARTERA RADIEX
Regla analítica:
- El cliente abona a la factura general.
- Para análisis gerencial, los abonos se aplican primero a Radiex.
- Si lo abonado cubre el total Radiex, saldo Radiex = 0.
- Si Radiex queda saldado, días vencidos = 0.
=============================================
*/

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
        WHEN SUM(t.saldo_rdx) > 0
        THEN 'CREDITO'
        ELSE 'CONTADO'
    END tipo_pago,

    CASE
        WHEN SUM(t.saldo_rdx) > 0
        THEN SUM(t.saldo_rdx)
        ELSE SUM(t.contado_monto)
    END pendiente,

    MAX(t.dias_vencidos) dias_vencidos,

    t.vendedor

FROM (

    SELECT

        v.idventa,
        DATE(v.fecha_registro) fecha,

        c.primer_nombre cliente,

        dep.nombre departamento,
        mun.nombre municipio,

        COALESCE(
            e.nombre,
            'SIN VENDEDOR'
        ) vendedor,

        /*
        =============================================
        SALDO RADIEX ESTRATÉGICO
        =============================================
        */
        CASE

            WHEN IFNULL(sc.saldo, 0) = 0
            THEN 0

            WHEN (
                tot.total_factura - IFNULL(sc.saldo, 0)
            ) >= tot.total_rdx
            THEN 0

            ELSE ROUND(
                tot.total_rdx -
                (
                    tot.total_factura - IFNULL(sc.saldo, 0)
                ),
                2
            )

        END saldo_rdx,

        /*
        RECUPERADO COMO CONTADO
        */
        CASE

            WHEN IFNULL(sc.saldo, 0) = 0
            THEN tot.total_rdx

            WHEN (
                tot.total_factura - IFNULL(sc.saldo, 0)
            ) >= tot.total_rdx
            THEN tot.total_rdx

            ELSE 0

        END contado_monto,

        /*
        DÍAS VENCIDOS
        */
        CASE

            WHEN
                CASE

                    WHEN IFNULL(sc.saldo, 0) = 0
                    THEN 0

                    WHEN (
                        tot.total_factura - IFNULL(sc.saldo, 0)
                    ) >= tot.total_rdx
                    THEN 0

                    ELSE ROUND(
                        tot.total_rdx -
                        (
                            tot.total_factura - IFNULL(sc.saldo, 0)
                        ),
                        2
                    )

                END > 0

            THEN DATEDIFF(
                CURDATE(),
                v.fecha_registro
            )

            ELSE 0

        END dias_vencidos

    FROM adm_venta v

    /*
    TOTAL FACTURA + TOTAL RADIEX
    */
    JOIN (
        SELECT
            d.idventa,
            SUM(d.total) total_factura,
            SUM(
                CASE
                    WHEN p.codigormym LIKE '%RDX%'
                    THEN d.total
                    ELSE 0
                END
            ) total_rdx
        FROM adm_detalle_venta d
        JOIN adm_producto p
            ON p.idproducto = d.idproducto
        GROUP BY d.idventa
    ) tot
        ON tot.idventa = v.idventa

    JOIN {$schemaClientes}.clientes c
        ON c.idcliente = v.id_cliente

    LEFT JOIN {$schemaClientes}.adm_departamentopais dep
        ON dep.iddepartamento = c.iddepartamento

    LEFT JOIN {$schemaClientes}.adm_municipio mun
        ON mun.id_municipio = c.id_municipio

    /*
    VENDEDOR DESDE PEDIDO O MOSTRADOR
    */
    LEFT JOIN (
        SELECT
            idpedido,
            MAX(id_empleado) id_empleado
        FROM pedido_producto
        GROUP BY idpedido
    ) pp
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

    /*
    CUENTAS POR COBRAR
    */
    LEFT JOIN saldoxcobrar sc
        ON sc.idventa = v.idventa

    WHERE

        v.estado = 1

        AND v.tipo IN ('F','E')

        AND NOT (
            v.tipo = 'F'
            AND v.id_envio > 0
        )

        AND tot.total_rdx > 0

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
        tot.total_factura,
        tot.total_rdx

) t

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

/*
=============================================
KPI TOTAL CARTERA RADIEX
Misma regla analítica que el detalle.
=============================================
*/

$totalSql = "

SELECT

IFNULL(
    SUM(
        CASE

            WHEN ? = 'CONTADO'
            THEN
                CASE

                    WHEN IFNULL(sc.saldo, 0) = 0
                    THEN tot.total_rdx

                    WHEN (tot.total_factura - IFNULL(sc.saldo, 0)) >= tot.total_rdx
                    THEN tot.total_rdx

                    ELSE 0

                END

            WHEN ? = 'CREDITO'
            THEN
                CASE

                    WHEN IFNULL(sc.saldo, 0) = 0
                    THEN 0

                    WHEN (tot.total_factura - IFNULL(sc.saldo, 0)) >= tot.total_rdx
                    THEN 0

                    ELSE ROUND(
                        tot.total_rdx -
                        (
                            tot.total_factura - IFNULL(sc.saldo, 0)
                        ),
                        2
                    )

                END

            WHEN ? = 'TODOS'
            THEN
                CASE

                    WHEN IFNULL(sc.saldo, 0) = 0
                    THEN tot.total_rdx

                    WHEN (tot.total_factura - IFNULL(sc.saldo, 0)) >= tot.total_rdx
                    THEN tot.total_rdx

                    ELSE ROUND(
                        tot.total_rdx -
                        (
                            tot.total_factura - IFNULL(sc.saldo, 0)
                        ),
                        2
                    )

                END

            ELSE 0

        END
    ),
    0
) total_monto

FROM adm_venta v

JOIN (
    SELECT
        d.idventa,
        SUM(d.total) total_factura,
        SUM(
            CASE
                WHEN p.codigormym LIKE '%RDX%'
                THEN d.total
                ELSE 0
            END
        ) total_rdx
    FROM adm_detalle_venta d
    JOIN adm_producto p
        ON p.idproducto = d.idproducto
    GROUP BY d.idventa
) tot
    ON tot.idventa = v.idventa

LEFT JOIN saldoxcobrar sc
    ON sc.idventa = v.idventa

JOIN {$schemaClientes}.clientes c
    ON c.idcliente = v.id_cliente

WHERE

v.estado = 1

AND v.tipo IN('F','E')

AND NOT (
    v.tipo = 'F'
    AND v.id_envio > 0
)

AND tot.total_rdx > 0

AND DATE(v.fecha_registro)
BETWEEN ?
AND ?

$filtro

";

$t = $conn->prepare($totalSql);

$t->bind_param(
    "sssss",
    $tipoPago,
    $tipoPago,
    $tipoPago,
    $fechaInicio,
    $fechaFinal
);

$t->execute();

$row = $t->get_result()->fetch_assoc();
$totalPendiente = (float)$row['total_monto'];

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