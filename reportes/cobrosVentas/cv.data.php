<?php
require_once __DIR__ . '/../../includes/db_connect.php';

// Consulta SQL
$fechaInicio = $_POST["fechaInicio"];
$fechaFinal  = $_POST["fechaFinal"];
$sucursalId  = $_POST["sucursal"];
$conexion = null;
if ($sucursalId == 1) {
    $conexion = connectToDatabase('central');
} else if ($sucursalId == 2) {
    $conexion = connectToDatabase('peten');
} else if ($sucursalId == 3) {
    $conexion = connectToDatabase('xela');
}

$filtro   = "";
$consulta = "";

if ($sucursalId == 1) {
    $consulta = "SELECT
r.nombre_vendedor,
r.semana,
sum(r.monto_facturas) as total_cobro
from adm_recibo r
join db_rmym.clientes cl on r.idcliente = cl.idcliente
join db_rmym.adm_departamentopais dp on cl.iddepartamento = dp.iddepartamento
join db_rmym.adm_municipio m on cl.id_municipio = m.id_municipio
where r.estado > 0 and
date(r.fecha_registro) >= ? and date(r.fecha_registro) <= ?";
} else if ($sucursalId == 2) {
    $consulta = "SELECT
r.nombre_vendedor,
r.semana,
sum(r.monto_facturas) as total_cobro
from adm_recibo r
join db_rmympt.clientes cl on r.idcliente = cl.idcliente
join db_rmympt.adm_departamentopais dp on cl.iddepartamento = dp.iddepartamento
join db_rmympt.adm_municipio m on cl.id_municipio = m.id_municipio
where r.estado > 0 and
date(r.fecha_registro) >= ? and date(r.fecha_registro) <= ?";
} else if ($sucursalId == 3) {
    $consulta = "SELECT
r.nombre_vendedor,
r.semana,
sum(r.monto_facturas) as total_cobro
from adm_recibo r
join db_rmymxela.clientes cl on r.idcliente = cl.idcliente
join db_rmymxela.adm_departamentopais dp on cl.iddepartamento = dp.iddepartamento
join db_rmymxela.adm_municipio m on cl.id_municipio = m.id_municipio
where r.estado > 0 and
date(r.fecha_registro) >= ? and date(r.fecha_registro) <= ?";
}

$grupo = " group by r.nombre_vendedor ";
$orden = " order by r.nombre_vendedor; ";

$consulta = $consulta . $filtro . $grupo . $orden;

$stmt = $conexion->prepare($consulta);

if (! $stmt) {
    echo "<script>alertify.error('Error en la consulta SQL');</script>";
    exit;
}

if ($conexion->connect_error) {
    echo "Hay error";
    die("Error de conexión: " . $conexion->connect_error);
}

//$resultado = $mysqli->query($consulta);

$stmt->bind_param("ss", $fechaInicio, $fechaFinal);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado) {
    //$datos = $resultado->fetch_all(MYSQLI_ASSOC);
    if ($resultado->num_rows > 0) {
        $return_arr = [];
        $indice     = 0;
        while ($row = $resultado->fetch_array()) {
            $return_arr[$indice] = [
                'nombre_vendedor' => $row['nombre_vendedor'],
                'semana'          => $row['semana'],
                'total_cobro'     => $row['total_cobro'],
                'total_venta'     => 0,
            ];
            $indice++;
        }

        //echo json_encode($return_arr);
        $resultado->close();
    } else {
        //$codigoRespuesta = -3; //no hay registros
        $resultado->close();
    }
} else {
    echo "Error en la consulta: " . $conexion->error;
    $datos = null;
}

/**
 * Consulta para ventas
 */
$consulta = "SELECT
e.nombre as nombre_vendedor,
sum(v.total) as total_venta
from adm_venta v
join pedido_producto p on v.idpedido = p.idpedido
join adm_empleado e on p.id_empleado = e.id_empleado
where
v.estado = 1
and v.tipo in('E','F')
and v.id_envio = 0
and date(v.fecha_registro) >= ? and date(v.fecha_registro) <= ?
group by e.nombre
order by e.nombre;";

$stmt = $conexion->prepare($consulta);

if (! $stmt) {
    echo "<script>alertify.error('Error en la consulta SQL');</script>";
    exit;
}

if ($conexion->connect_error) {
    echo "Hay error";
    die("Error de conexión: " . $conexion->connect_error);
}

//$resultado = $mysqli->query($consulta);

$stmt->bind_param("ss", $fechaInicio, $fechaFinal);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado) {
    //$datos = $resultado->fetch_all(MYSQLI_ASSOC);
    if ($resultado->num_rows > 0) {
        $indice = 0;
        while ($row = $resultado->fetch_array()) {
            //foreach($return_arr as $item)
            for ($i = 0; $i < sizeof($return_arr); $i++) {
                if ($return_arr[$i]["nombre_vendedor"] === $row["nombre_vendedor"]) {
                    //$item["total_venta"] = $row["total_venta"];
                    $return_arr[$i]["total_venta"] = $row["total_venta"];
                    break;
                }
            }
        }

        //echo json_encode($return_arr);
        $resultado->close();
    } else {
        //$codigoRespuesta = -3; //no hay registros
        $resultado->close();
    }
} else {
    echo "Error en la consulta: " . $conexion->error;
    $datos = null;
}

$conexion->close();
echo json_encode($return_arr);
