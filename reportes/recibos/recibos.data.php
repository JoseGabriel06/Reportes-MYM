<?php
require_once '../../connection.php';
// Consulta SQL
$fechaInicio = $_POST["fechaInicio"];
$fechaFinal = $_POST["fechaFinal"];
$departamento = $_POST["departamento"];
$municipio = $_POST["municipio"];
$vendedor = $_POST["vendedor"];

$filtro = "";
// Consulta SQL
$consulta = "SELECT
r.id_recibo,
d.id_detalle_recibo,
r.fecha_registro,
r.semana,
e.nombre as ejecutivo,
d.no_envio,
d.monto,
d.abono,
d.saldo,
d.pago,
r.fecha_recibo,
year(r.fecha_recibo) as anio,
concat(r.serie_recibo,' ',r.no_recibo) as recibo,
r.cobro,
if(ro.idreciboventa is null,'NO OPERADO',concat(ro.serie_recibo,' ',ro.norecibo)) as recibo_operado,
if(ro.idreciboventa is null,0,ro.monto_facturas) as cobro_operado,
if(ro.idreciboventa is null,'',ro.concepto) as concepto_operado,
if(ro.idreciboventa is null,'',ro.usuario) as usuario_opera,
if(ro.idreciboventa is null,'',ro.nombre_vendedor) as vendedor_operado,
r.observacion,
d.no_deposito,
d.no_cheque,
d.pago_total,
d.banco,
d.cobrado,
r.nombre_cliente,
dp.nombre as departamento,
m.nombre as municipio
from db_rmym.vnt_registro_recibo r 
join db_rmym.vnt_detalle_recibo d on r.id_recibo = d.id_recibo
join db_rmym.clientes cl on r.id_cliente = cl.idcliente
join db_rmym.adm_departamentopais dp on cl.iddepartamento = dp.iddepartamento
join db_rmym.adm_municipio m on cl.id_municipio = m.id_municipio
join db_rmym.adm_usuario u on r.id_usuario = u.idadm_usuario
join db_rmym.adm_empleado e on u.id_empleado = e.id_empleado 
left join db_mymsa.adm_recibo ro on r.id_recibo = ro.idreciboventa and ro.estado = 1
where 
date(r.fecha_recibo) >= ? and date(r.fecha_recibo) <= ?
and r.estado > 0 ";

if($departamento != "TODOS")
{
    $filtro = "and dp.nombre = '$departamento' ";
}

if($municipio != "TODOS" && strlen($municipio) != 0)
{
   $filtro = $filtro . "and m.nombre = '$municipio' ";
}

if($vendedor != "TODOS")
{
    $filtro = $filtro . "and ro.nombre_vendedor = '$vendedor' ";
}
$orden = "order by ejecutivo,semana;";
$consulta = $consulta . $filtro . $orden;

$stmt = $mysqli->prepare($consulta);

    if (!$stmt) {
        echo "<script>alertify.error('Error en la consulta SQL');</script>";
        exit;
    }

    if ($mysqli->connect_error) {
        echo "Hay error";
        die("Error de conexión: " . $mysqli->connect_error);
    }

    //$resultado = $mysqli->query($consulta);

    $stmt->bind_param("ss", $fechaInicio, $fechaFinal);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado) {
        $datos = $resultado->fetch_all(MYSQLI_ASSOC);
        $mysqli->close();
        //return $datos;
    } else {
        echo "Error en la consulta: " . $mysqli->error;
        $mysqli->close();
        $datos = null;
    }

echo json_encode($datos);
?>