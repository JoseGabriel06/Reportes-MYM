<?php
require_once __DIR__ . '/../../includes/db_connect.php';
$conexion = connectToDatabase('central');

$codigoProducto = $_POST["codigo"];

$codigoRespuesta = 1;

if ($conexion !== null && $conexion->connect_errno === 0) {
    $stmt = "select " .
    "d.nombre as departamento," .
    "m.nombre as municipio," .
    "c.primer_nombre as cliente," .
    "sum(dv.cantidad) as cantidad," .
    "dv.precio," .
    "sum(dv.subtotal) as subtotal," .
    "max(v.fecha_registro) as ultima_compra," . //para tomar la información de tiempo
    "if(sum(dd.cantidad_devuelta) is null,0,sum(dd.cantidad_devuelta)) as cantidad_devuelta," .
    "if(dd.precio_unitario is null,0,dd.precio_unitario) as precio_unitario_venta," .
    "if(sum(dd.subtotal) is null,0,sum(dd.subtotal)) as subtotal_devolucion," .
    "if(dvl.tipo is null,'',dvl.tipo) as sobre," .
    "group_concat(distinct mp.nombre) as marca " .
    "from adm_venta v " .
    "join adm_detalle_venta dv on v.idventa = dv.idventa " .
    "join adm_producto p on dv.idproducto = p.idproducto " .
    "join adm_marca_producto mp on p.idmarca = mp.idmarca " .
    "left join adm_devolucion dvl on v.idventa = dvl.idventa and dvl.estado > 0 " .
    "left join adm_detalle_devolucion dd on dvl.iddevolucion = dd.iddevolucion and p.idproducto = dd.idproducto and dd.estado = 1 " .
    "join `db_rmym`.`clientes` c on v.id_cliente = c.idcliente " .
    "join `db_rmym`.`adm_departamentopais` d on c.iddepartamento = d.iddepartamento " .
    "join `db_rmym`.`adm_municipio` m on c.id_municipio = m.id_municipio " .
    "where v.estado > 0 " .
    "and v.tipo in('E','F') " . //# los registros pueden ser de envíos y/o facturas
    "and v.id_envio = 0 " .     #son facturas que no provienen de un envío, sino que son directas
    "and dv.estado = 1 " .
    "and p.codigormym = '$codigoProducto' " .
    "group by cliente " .
    "union " .
    "select " .
    "d.nombre as departamento," .
    "m.nombre as municipio," .
    "c.primer_nombre as cliente," .
    "sum(dv.cantidad) as cantidad," .
    "dv.precio," .
    "sum(dv.subtotal) as subtotal," .
    "max(v.fecha_registro) as ultima_compra," . //#para tomar la información de tiempo
    "if(sum(dd.cantidad_devuelta) is null,0,sum(dd.cantidad_devuelta)) as cantidad_devuelta," .
    "if(dd.precio_unitario is null,0,dd.precio_unitario) as precio_unitario_venta," .
    "if(sum(dd.subtotal) is null,0,sum(dd.subtotal)) as subtotal_devolucion," .
    "if(dvl.tipo is null,'',dvl.tipo) as sobre," .
    "group_concat(distinct mp.nombre) as marca " .
    "from adm_venta v " .
    "join adm_detalle_venta dv on v.idventa = dv.idventa " .
    "join adm_producto p on dv.idproducto = p.idproducto " .
    "join adm_marca_producto mp on p.idmarca = mp.idmarca " .
    "join adm_devolucion dvl on v.idventa = dvl.idventa " .
    "join adm_detalle_devolucion dd on dvl.iddevolucion = dd.iddevolucion and p.idproducto = dd.idproducto and dd.estado = 1 " .
    "join `db_rmym`.`clientes` c on v.id_cliente = c.idcliente " .
    "join `db_rmym`.`adm_departamentopais` d on c.iddepartamento = d.iddepartamento " .
    "join `db_rmym`.`adm_municipio` m on c.id_municipio = m.id_municipio " .
    "where v.estado > 0 " .
    "and dvl.estado = 1 " .
    "and v.tipo = 'F' " . //# los registros pueden ser de envíos y/o facturas
    "and dv.estado = 1 " .
        "and p.codigormym = '$codigoProducto' " .
        "group by cliente " .
        "order by cliente;";

    $result = $conexion->query($stmt);

    if ($result !== false) {
        if ($result->num_rows > 0) {
            $return_arr = [];
            $indice     = 0;
            while ($row = $result->fetch_array()) {
                $return_arr[$indice] = [
                    'departamento'          => $row['departamento'],
                    'municipio'             => $row['municipio'],
                    'cliente'               => $row['cliente'],
                    'cantidad'              => $row['cantidad'],
                    'precio'                => $row['precio'],
                    'subtotal'              => $row['subtotal'],
                    'ultima_compra'         => $row['ultima_compra'],
                    'cantidad_devuelta'     => $row['cantidad_devuelta'],
                    'precio_unitario_venta' => $row['precio_unitario_venta'],
                    'subtotal_devolucion'   => $row['subtotal_devolucion'],
                    'sobre'                 => $row['sobre'],
                    'marca'                 => $row['marca'],
                ];
                $indice++;
            }

            echo json_encode($return_arr);
            $result->close();
        } else {
            $codigoRespuesta = -3; //no hay registros
            $result->close();
        }
    } else {
        $codigoRespuesta = -2; //fallo en la consulta
    }
    $conexion->close();
} else {
    $codigoRespuesta = -1; //fallo de conexión
}

if ($codigoRespuesta != 1) {
    $mensajeRespuesta = "";
    switch ($codigoRespuesta) {
        case -1:{
                $mensajeRespuesta = "FALLO DE CONEXION";
                break;
            }
        case -2:{
                $mensajeRespuesta = "FALLO DE CONSULTA";
                break;
            }
        case -3:{
                $mensajeRespuesta = "NO HAY REGISTROS";
                break;
            }
    }

    $return_arr          = [];
    $Indice              = 0;
    $return_arr[$Indice] = [
        'departamento'          => '',
        'municipio'             => '',
        'cliente'               => '',
        'cantidad'              => 0,
        'precio'                => 0.00,
        'subtotal'              => 0.00,
        'ultima_compra'         => '',
        'cantidad_devuelta'     => 0,
        'precio_unitario_venta' => 0.00,
        'subtotal_devolucion'   => 0.00,
        'sobre'                 => '',
        'marca'                 => '',
    ];

    echo json_encode($return_arr);
}
?>