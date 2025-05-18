<?php
session_start(); // Inicia sesión

// Verifica si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../../../login/login.php'); // Redirige al login si no está autenticado
    exit;
}
?>
<!-- PARA LA TABLA -->
<?php
$consulta = "SELECT 
dp.nombre as departamento,
m.nombre as municipio,
e.nombre as vendedor,
c.primer_nombre as cliente,
v.seriefactura,v.nofactura,
s.monto,s.abono,s.saldo,
v.fecha_registro as fecha_envio,
s.fecha_vencimiento,
if (datediff(now(), s.fecha_vencimiento) < 0,0,datediff(now(), s.fecha_vencimiento)) as dias_vencidos 
from adm_venta v 
join saldoxcobrar s on v.idventa = s.idventa 
join db_rmym.clientes c on v.id_cliente = c.idcliente 
join db_rmym.adm_empleado e on c.id_empleado = e.id_empleado
join db_rmym.adm_departamentopais dp on c.iddepartamento = dp.iddepartamento
join db_rmym.adm_municipio m on c.id_municipio = m.id_municipio
where s.estado = 1 
and v.estado > 0 
and s.saldo > 0 
and v.tipo in('E', 'F') 
and v.id_envio = 0
order by departamento,municipio;";

// Función para obtener los datos
function obtenerDatosDeBaseDeDatos($consulta)
{
    $servidor = '181.114.25.86';
    $usuario = 'usr_mym';
    $contrasena = 'Mym*20#*81@_)';
    $port = 3307;
    $baseDeDatos = 'db_mymsa';

    $conexion = new mysqli($servidor, $usuario, $contrasena, $baseDeDatos,$port);

    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    $resultado = $conexion->query($consulta);

    if ($resultado) {
        $datos = $resultado->fetch_all(MYSQLI_ASSOC);
        $conexion->close();
        return $datos;
    } else {
        echo "Error en la consulta: " . $conexion->error;
        $conexion->close();
        return null;
    }
}

// Llamar la función y obtener los datos
$resultado = obtenerDatosDeBaseDeDatos($consulta);
?>
<?php
// Obtener opciones únicas para filtros
function obtenerOpcionesFiltro($campo, $tabla) {
    $servidor = '181.114.25.86';
    $usuario = 'usr_mym';
    $contrasena = 'Mym*20#*81@_)';
    $port = 3307;
    $baseDeDatos = 'db_mymsa';

    $conexion = new mysqli($servidor, $usuario, $contrasena, $baseDeDatos,$port);

    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    $consulta = "SELECT DISTINCT $campo FROM $tabla ORDER BY $campo";
    $resultado = $conexion->query($consulta);

    $opciones = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $opciones[] = $fila[$campo];
        }
    }

    $conexion->close();
    return $opciones;
}

// Obtener opciones para cada filtro
$departamentos = obtenerOpcionesFiltro('nombre', 'adm_departamentopais');
$municipios = obtenerOpcionesFiltro('nombre', 'adm_municipio');
$vendedores = obtenerOpcionesFiltro('nombre', 'adm_empleado');
?>
<!-- PARA LA GRÁFICA -->
<?php
// Inicializar acumuladores para los totales
$totalMonto = 0;
$totalAbono = 0;
$totalSaldo = 0;

// Sumar cada columna
foreach ($resultado as $fila) {
    $totalMonto += $fila['monto'];
    $totalAbono += $fila['abono'];
    $totalSaldo += $fila['saldo'];
}

// Preparar datos para Chart.js
$totals = [
    'monto' => $totalMonto,
    'abono' => $totalAbono,
    'saldo' => $totalSaldo
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://i.imgur.com/RQXNwMZ.png">
    <!-- Fuentes de iconos -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Fuentes para letra -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <!-- Menu sidebar -->
    <link rel="stylesheet" href="../../../css/menuPrincipal.css">
    <!-- Tabla de data table -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="../../../css/datatables.css" />
    <link rel="stylesheet" href="../../../css/datatables.min.css" />
    <link rel="stylesheet" href="css/estilos.css">
    <title>Reporte</title>
</head>
<body>
<!-- ---------- -->
<nav id="sidebar">
  <ul>
    <li>
      <span class="logo">Distruidora MYM</span>
      <button onclick=toggleSidebar() id="toggle-btn">
        <i class='bx bx-chevrons-left' ></i>
      </button>
    </li>
    <li class="active">
      <a href="index.php">
        <i class='bx bx-home'></i>
        <span>Inicio</span>
      </a>
    </li>
    <li>
      <button onclick=toggleSubMenu(this) class="dropdown-btn">
        <i class='bx bxs-report'></i>
        <span class="texto_menu">Reporte</span>
        <i class='bx bx-chevron-down'></i>
      </button>

      <ul class="sub-menu">
        <div>
          <li>
            <button onclick=toggleSubMenu2(this) class="dropdown-btn">
              <span class="texto_menu">Saldos</span>
              <i class='bx bx-chevron-down'></i>
            </button>
            <ul class="sub-menu2">
              <div>
                <li><a href="#">Central</a></li>
                <li><a href="../peten">Peten</a></li>
                <li><a href="../xela">Xela</a></li>
              </div>
            </ul>
          </li>
          <li><a href="../../recibos">Recibos</a></li>
          <li><a href="../../cobrosVentas">Resumen CV</a></li>
          <li><a href="../../productoClientes">Producto Clientes</a></li>
        </div>
      </ul>
    </li>

    <li class="log_out">
      <a href="../../../login/logout.php">
        <i class='bx bx-log-out'></i>
        <span>Cerrar Sesión</span>
      </a>
    </li>
  </ul>
</nav>
  <main id="contenedorContenido">
  <div class="contenedor_titulo">
        <h2>SALDOS</h2>
    </div>

    <div class="contenedor_tabla">

<div class="filtros">
    <select id="filtro-departamento" class="selectores">
        <option value="">Todos los Departamentos</option>
        <?php foreach ($departamentos as $departamento): ?>
            <option value="<?php echo $departamento; ?>"><?php echo $departamento; ?></option>
        <?php endforeach; ?>
    </select>

    <select id="filtro-municipio" class="selectores">
        <option value="">Todos los Municipios</option>
    </select>

    <select id="filtro-vendedor" class="selectores">
        <option value="">Todos los Vendedores</option>
        <?php foreach ($vendedores as $vendedor): ?>
            <option value="<?php echo $vendedor; ?>"><?php echo $vendedor; ?></option>
        <?php endforeach; ?>
    </select>
</div>


<table id="tabla-ventas" class="display" style=" z-index: 1;">
        <thead>
            <tr>
                <th>Departamento</th>
                <th>Municipio</th>
                <th>Vendedor</th>
                <th>Cliente</th>
                <th>Serie Factura</th>
                <th>No.Factura</th>
                <th>Monto</th>
                <th>Abono</th>
                <th>Saldo</th>
                <th>Fecha Envío</th>
                <th>Fecha Vencimiento</th>
                <th>Días Vencidos</th>
            </tr>
        </thead>
        <tfoot>
        <tr>
            <th colspan="6" style="text-align:right;">Totales:</th>
            <th id="total-monto">Q 0.00</th>
            <th id="total-abono">Q 0.00</th>
            <th id="total-saldo">Q 0.00</th>
            <th colspan="3"></th>
        </tr>
    </tfoot>
        <tbody>
            <!-- Se llenará dinámicamente con JavaScript -->
        </tbody>
    </table>
    </div>
    <div class="contenedor_grafica">
    <button class="exp_pdf" id="exportPDF">PDF</button>
    <div class="grafica">
    <canvas id="myChart"></canvas>
    </div>
    </div>
  </main>
    <script src="../../../js/sidebar.js"></script>

<!-- -------- -->
<!-- Gráfica -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>


<script>
      // Inicializar Chart.js
      const totals = <?php echo json_encode($totals); ?>;
    const ctx = document.getElementById('myChart').getContext('2d');
    let myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Monto', 'Abono', 'Saldo'],
            datasets: [
                {
                    label: 'Totales',
                    data: [totals.monto, totals.abono, totals.saldo],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(153, 102, 255, 0.2)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Totales de Monto, Abono y Saldo'
                },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    formatter: function (value) {
                        return `Q ${Number(value).toLocaleString('es-GT', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })}`;
                    },
                    font: {
                        size: 12,
                        weight: 'bold'
                    },
                    color: '#000'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        },
        plugins: [ChartDataLabels]
    });

    // Función para actualizar los datos de la gráfica
    function actualizarGrafica(api) {
        const totalMonto = api
            .column(6, { filter: 'applied' })
            .data()
            .reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

        const totalAbono = api
            .column(7, { filter: 'applied' })
            .data()
            .reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

        const totalSaldo = api
            .column(8, { filter: 'applied' })
            .data()
            .reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

        // Actualizar datos de la gráfica
        myChart.data.datasets[0].data = [totalMonto, totalAbono, totalSaldo];
        myChart.update();
    }
  
    // TABLA
    $(document).ready(function () {
        const table = $("#tabla-ventas").DataTable({
            paging: false,
            scrollCollapse: true,
            scrollY: '400px',
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            },
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'pdfHtml5',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    text: 'Exportar a PDF',
                    title: 'SALDOS',
                    exportOptions: {
                        columns: ':visible'
                    },
                    customize: function (doc) {
                        const totalMonto = table.column(6, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
                        const totalAbono = table.column(7, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
                        const totalSaldo = table.column(8, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

                        const totalRow = [
                            '', '', '', '', '', 'Totales:',
                            `Q ${totalMonto.toLocaleString('es-GT', { minimumFractionDigits: 2 })}`,
                            `Q ${totalAbono.toLocaleString('es-GT', { minimumFractionDigits: 2 })}`,
                            `Q ${totalSaldo.toLocaleString('es-GT', { minimumFractionDigits: 2 })}`,
                            '', '', ''
                        ];
                        doc.content[1].table.body.push(totalRow);

                        doc.styles.tableHeader = { fontSize: 9, bold: true, alignment: 'center' };
                        doc.defaultStyle.fontSize = 8;
                        doc.pageMargins = [10, 10, 10, 10];
                    }
                },
                {
                    extend: 'excelHtml5',
                    text: 'Exportar a Excel',
                    title: 'SALDOS',
                    exportOptions: {
                        columns: ':visible'
                    },
                    customizeData: function (data) {
                        const totalMonto = table.column(6, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
                        const totalAbono = table.column(7, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
                        const totalSaldo = table.column(8, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

                        const totalRow = ['', '', '', '', '', 'Totales:', totalMonto.toFixed(2), totalAbono.toFixed(2), totalSaldo.toFixed(2), '', '', ''];
                        data.body.push(totalRow);
                    }
                }
            ],
            footerCallback: function (row, data, start, end, display) {
                calculateTotals(this.api());
            }
        });

        // Actualizar gráfica al redibujar la tabla
        table.on('draw', function () {
            actualizarGrafica(table);
        });

        // Escuchar filtros dinámicos
        $('#filtro-departamento, #filtro-municipio, #filtro-vendedor').on('change', function () {
            const departamento = $('#filtro-departamento').val();
            const municipio = $('#filtro-municipio').val();
            const vendedor = $('#filtro-vendedor').val();

            table.column(0).search(departamento || '');
            table.column(1).search(municipio || '');
            table.column(2).search(vendedor || '');
            table.draw();
        });
    });
    </script>

<!-- Data Table -->
<script src="../../../js/jquery-3.7.1.js"></script>
<script src="../../../js/datatables.js"></script>
<script src="../../../js/datatables.min.js"></script>
<!-- JSZip para exportar a Excel -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<!-- pdfmake para exportar a PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<!-- Botones de exportación -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

   <script>
$(document).ready(function () {
    // Datos generados desde PHP
    const datos = <?php echo json_encode($resultado); ?>;

    // Llenar la tabla con los datos
    const tablaCuerpo = $("#tabla-ventas tbody");
    datos.forEach(fila => {
        let filaHTML = "<tr>";
        filaHTML += `<td>${fila.departamento}</td>`;
        filaHTML += `<td>${fila.municipio}</td>`;
        filaHTML += `<td>${fila.vendedor}</td>`;
        filaHTML += `<td>${fila.cliente}</td>`;
        filaHTML += `<td>${fila.seriefactura}</td>`;
        filaHTML += `<td>${fila.nofactura}</td>`;
        filaHTML += `<td>${parseFloat(fila.monto).toFixed(2)}</td>`;
        filaHTML += `<td>${parseFloat(fila.abono).toFixed(2)}</td>`;
        filaHTML += `<td>${parseFloat(fila.saldo).toFixed(2)}</td>`;
        filaHTML += `<td>${fila.fecha_envio}</td>`;
        filaHTML += `<td>${fila.fecha_vencimiento}</td>`;
        filaHTML += `<td>${fila.dias_vencidos}</td>`;
        filaHTML += "</tr>";
        tablaCuerpo.append(filaHTML);
    });

    // Inicializar DataTable
    const table = $("#tabla-ventas").DataTable({
        paging: false,
        scrollCollapse: true,
        scrollY: '400px',
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'A4',
                text: 'Exportar a PDF',
                title: 'SALDOS',
                exportOptions: {
                    columns: ':visible' // Exportar solo columnas visibles
                },
                customize: function (doc) {
                    // Obtener totales directamente desde la tabla
                    const totalMonto = table.column(6, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
                    const totalAbono = table.column(7, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
                    const totalSaldo = table.column(8, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

                    // Agregar totales al final del PDF
                    const totalRow = [
                        '', '', '', '', '', 'Totales:',
                        `Q ${totalMonto.toLocaleString('es-GT', { minimumFractionDigits: 2 })}`,
                        `Q ${totalAbono.toLocaleString('es-GT', { minimumFractionDigits: 2 })}`,
                        `Q ${totalSaldo.toLocaleString('es-GT', { minimumFractionDigits: 2 })}`,
                        '', '', ''
                    ];
                    doc.content[1].table.body.push(totalRow);

                    // Ajustar tamaño de fuente y márgenes
                    doc.styles.tableHeader = { fontSize: 9, bold: true, alignment: 'center' };
                    doc.defaultStyle.fontSize = 8; // Reducir el tamaño de fuente
                    doc.pageMargins = [10, 10, 10, 10]; // Márgenes compactos
                }
            },
            {
                extend: 'excelHtml5',
                text: 'Exportar a Excel',
                title: 'SALDOS',
                exportOptions: {
                    columns: ':visible' // Exportar solo columnas visibles
                },
                customizeData: function (data) {
                    // Obtener totales directamente desde la tabla
                    const totalMonto = table.column(6, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
                    const totalAbono = table.column(7, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
                    const totalSaldo = table.column(8, { filter: 'applied' }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

                    // Agregar fila de totales a los datos exportados
                    const totalRow = ['', '', '', '', '', 'Totales:', totalMonto.toFixed(2), totalAbono.toFixed(2), totalSaldo.toFixed(2), '', '', ''];
                    data.body.push(totalRow);
                }
            }
        ],
        footerCallback: function (row, data, start, end, display) {
            calculateTotals(this.api());
        }
    });
    // Función para recalcular totales visibles y actualizar la gráfica
function actualizarGraficaDesdeTabla(table) {
    // Recalcular los totales visibles de la tabla
    const totalMonto = table
        .column(6, { filter: 'applied' })
        .data()
        .reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

    const totalAbono = table
        .column(7, { filter: 'applied' })
        .data()
        .reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

    const totalSaldo = table
        .column(8, { filter: 'applied' })
        .data()
        .reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

    // Actualizar los datos del gráfico
    myChart.data.datasets[0].data = [totalMonto, totalAbono, totalSaldo];
    myChart.update(); // Redibujar la gráfica con los nuevos datos
}

// Escuchar el evento `draw` para actualizar la gráfica
table.on('draw', function () {
    actualizarGraficaDesdeTabla(table);
});


    // Función para calcular y actualizar los totales en el footer
    function calculateTotals(api) {
        const totalMonto = api
            .column(6, { filter: 'applied' })
            .data()
            .reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

        const totalAbono = api
            .column(7, { filter: 'applied' })
            .data()
            .reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

        const totalSaldo = api
            .column(8, { filter: 'applied' })
            .data()
            .reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

        // Actualizar footer con los totales formateados como moneda
        $(api.column(6).footer()).html(`Q ${totalMonto.toLocaleString('es-GT', { minimumFractionDigits: 2 })}`);
        $(api.column(7).footer()).html(`Q ${totalAbono.toLocaleString('es-GT', { minimumFractionDigits: 2 })}`);
        $(api.column(8).footer()).html(`Q ${totalSaldo.toLocaleString('es-GT', { minimumFractionDigits: 2 })}`);
    }

    // Escuchar el evento `draw` para recalcular los totales dinámicamente
    table.on('draw', function () {
        calculateTotals(table);
    });

    // Filtros dinámicos: Departamento, Municipio, Vendedor
    $('#filtro-departamento, #filtro-municipio, #filtro-vendedor').on('change', function () {
        // Obtener valores de los filtros
        const departamento = $('#filtro-departamento').val();
        const municipio = $('#filtro-municipio').val();
        const vendedor = $('#filtro-vendedor').val();

        // Aplicar filtros
        table.column(0).search(departamento || ''); // Filtrar por Departamento (Columna 0)
        table.column(1).search(municipio || '');   // Filtrar por Municipio (Columna 1)
        table.column(2).search(vendedor || '');   // Filtrar por Vendedor (Columna 2)

        // Redibujar tabla
        table.draw();
    });
});
 // Exportar a PDF
 document.getElementById('exportPDF').addEventListener('click', async () => {
            const { jsPDF } = window.jspdf;

            // Capturar gráfica como imagen
            const imgData = myChart.toBase64Image();

            // Crear un archivo PDF y agregar la imagen
            const pdf = new jsPDF();
            pdf.addImage(imgData, 'PNG', 10, 10, 180, 100); // Ajustar las dimensiones según necesidad
            pdf.save('graficaSaldos.pdf'); // Descargar archivo
        });
    </script>
    <script src="js/selectDinamico.js"></script>
</body>
</html>

