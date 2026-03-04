<?php
/*
Este archivo:
Recibe fechaInicio
fechaFinal
sucursal
Ejecuta consulta pesada
Guarda resultado en tabla resumen
Incluye clasificación CONTADO vs CRÉDITO (regla 7 días)
*/

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

if (!isset($map[$sucursalId])) {
    echo json_encode(['ok'=>false,'error'=>'Sucursal inválida']);
    exit;
}

$conn = connectToDatabase($map[$sucursalId]);
$conn->set_charset("utf8mb4");

/* ===========================
   BORRAR RESUMEN ANTERIOR
=========================== */
$delete = $conn->prepare("
DELETE FROM resumen_rdx_dashboard
WHERE fecha_desde=? AND fecha_hasta=?
");
$delete->bind_param("ss",$fechaInicio,$fechaFinal);
$delete->execute();
$delete->close();

/* ===========================
   GENERAR NUEVO RESUMEN
=========================== */

$sql = "
SELECT 
    p.idproducto,
    p.codigormym,
    p.nombre,
    SUM(d.cantidad) cantidad,
    SUM(d.total) total_general,

    SUM(
        CASE 
            WHEN sc.saldo = 0 
                 AND r.fecha_recibo IS NOT NULL
                 AND DATEDIFF(r.fecha_recibo, v.fecha_registro) <= 7
            THEN d.total
            ELSE 0
        END
    ) AS total_contado,

    SUM(
        CASE 
            WHEN sc.saldo > 0 
                 OR r.fecha_recibo IS NULL
                 OR DATEDIFF(r.fecha_recibo, v.fecha_registro) > 7
            THEN d.total
            ELSE 0
        END
    ) AS total_credito

FROM adm_venta v
JOIN adm_detalle_venta d ON v.idventa=d.idventa
JOIN adm_producto p ON p.idproducto=d.idproducto

LEFT JOIN saldoxcobrar sc ON sc.idventa=v.idventa
LEFT JOIN adm_facturas_recibo fr ON fr.idventa=v.idventa
LEFT JOIN adm_recibo r ON r.idrecibo=fr.idrecibo

WHERE v.estado>0
AND v.tipo IN ('F','E')
AND NOT (v.tipo='F' AND v.id_envio>0)
AND p.codigormym LIKE '%RDX%'
AND DATE(v.fecha_registro) BETWEEN ? AND ?

GROUP BY p.idproducto,p.codigormym,p.nombre
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss",$fechaInicio,$fechaFinal);
$stmt->execute();
$res = $stmt->get_result();

while($r = $res->fetch_assoc()){

    $insert = $conn->prepare("
    INSERT INTO resumen_rdx_dashboard
    (fecha_desde,fecha_hasta,idproducto,codigormym,nombre,cantidad,total_general,total_contado,total_credito)
    VALUES (?,?,?,?,?,?,?,?,?)
    ");

    $insert->bind_param(
        "ssissdddd",
        $fechaInicio,
        $fechaFinal,
        $r['idproducto'],
        $r['codigormym'],
        $r['nombre'],
        $r['cantidad'],
        $r['total_general'],
        $r['total_contado'],
        $r['total_credito']
    );

    $insert->execute();
    $insert->close();
}

$stmt->close();
$conn->close();

echo json_encode(['ok'=>true]);