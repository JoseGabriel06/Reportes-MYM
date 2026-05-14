<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL);

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

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

    $fechaInicio = $_POST['fechaInicio'] ?? '';
    $fechaFinal  = $_POST['fechaFinal'] ?? '';
    $sucursal    = (int)($_POST['sucursal'] ?? 1);

    $map = [
        1 => 'central',
        2 => 'peten',
        3 => 'xela'
    ];

    if (
        !$fechaInicio ||
        !$fechaFinal ||
        !isset($map[$sucursal])
    ) {
        salir([
            'data' => [],
            'rows' => [],
            'totalCobrado' => 0
        ]);
    }

    $conn = connectToDatabase($map[$sucursal]);
    $conn->set_charset('utf8mb4');

    /*
    =========================================================
    COBROS RADIEX POR VENDEDOR
    REGLA GERENCIAL:
    - El cliente abona a la factura general.
    - Para análisis, Radiex se recupera primero.
    - Recuperado = total factura - saldo actual.
    - Ventas mostrador = contado automático.
    =========================================================
    */

    $sql = "

SELECT

    base.vendedor,

    ROUND(
        SUM(base.cobrado_rdx),
        2
    ) total_cobrado,

    COUNT(
        DISTINCT CASE
            WHEN base.cobrado_rdx > 0
            THEN base.idventa
        END
    ) ventas_cobradas,

    COUNT(
        DISTINCT CASE
            WHEN base.cobrado_rdx > 0
            THEN base.idventa
        END
    ) recibos

FROM (

    SELECT

        v.idventa,

        COALESCE(
            e.nombre,
            'SIN VENDEDOR'
        ) vendedor,

        tot.total_factura,
        tot.total_rdx,

        IFNULL(
            sc.saldo,
            0
        ) saldo_actual,

        LEAST(
            tot.total_rdx,
            GREATEST(
                tot.total_factura -
                IFNULL(sc.saldo, 0),
                0
            )
        ) cobrado_rdx

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

    LEFT JOIN saldoxcobrar sc
        ON sc.idventa = v.idventa

    WHERE

        v.estado > 0

        AND v.tipo IN (
            'F',
            'E'
        )

        AND NOT (
            v.tipo = 'F'
            AND v.id_envio > 0
        )

        AND tot.total_rdx > 0

        AND DATE(v.fecha_registro)
        BETWEEN ?
        AND ?

) base

GROUP BY
    base.vendedor

HAVING
    SUM(base.cobrado_rdx) > 0

ORDER BY
    base.vendedor

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
    $totalCobrado = 0;

    while ($r = $res->fetch_assoc()) {

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