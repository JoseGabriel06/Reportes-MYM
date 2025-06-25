<?php
session_start(); // Inicia sesión

// Verifica si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
  header('Location: ../../login/login.php'); // Redirige al login si no está autenticado
  exit;
}

$regiones = [
  1 => 'Central',
  2 => 'Petén',
  3 => 'Xela'
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
  <link rel="stylesheet" href="../../css/menuPrincipal.css">
  <!-- Tabla de data table -->
  <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <link rel="stylesheet" href="../../css/datatables.css" />
  <link rel="stylesheet" href="../../css/datatables.min.css" />
  <link rel="stylesheet" href="css/estilos.css">
  <title>Reporte</title>
</head>

<body onload="cargarFechas()">
  <nav id="sidebar">
    <ul>
      <li>
        <span class="logo">Distruidora MYM</span>
        <button onclick=toggleSidebar() id="toggle-btn">
          <i class='bx bx-chevrons-left'></i>
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
                  <li><a href="../saldos/central">Central</a></li>
                  <li><a href="../saldos/peten">Peten</a></li>
                  <li><a href="../saldos/xela">Xela</a></li>
                </div>
              </ul>
            </li>
            <li><a href="../recibos">Recibos</a></li>
            <li><a href="#">Resumen CV</a></li>
            <li><a href="../productoClientes">Producto Clientes</a></li>
          </div>
        </ul>
      </li>

      <li class="log_out">
        <a href="../../login/logout.php">
          <i class='bx bx-log-out'></i>
          <span>Cerrar Sesión</span>
        </a>
      </li>
    </ul>
  </nav>
  <main id="contenedorContenido">
    <div class="contenedor_titulo">
      <h2>RESUMEN COBROS Y VENTAS</h2>
    </div>

    <div class="contenedor_tabla">
    <div class="contenedor_filtros">
      <div class="contenedor_region">
        <div class="region">
        <label for="region" class="subtitulo_region">Región</label>
        <select name="region" id="region" class="select_region">
          <option value="" disabled selected>-- Selecciona una región --</option>
          <?php foreach ($regiones as $valor => $nombre): ?>
            <option value="<?php echo $valor; ?>">
              <?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
        </div>
      </div>

      <div class="fechas">
        <div class="fecha">
          <label for="fecha_inicio" class="subtitulo_fecha">Fecha Inicio</label>
          <input type="date" id="fecha_inicio" name="fecha_inicio" class="campo_fecha">
        </div>
        <div class="fecha">
          <label for="fecha_final" class="subtitulo_fecha">Fecha Final</label>
          <input type="date" id="fecha_final" name="fecha_final" class="campo_fecha">
        </div>
      </div>  
    </div>
  
      
      
      <div class="contenedor_btn">
        <button type="button" id="btnCarga" onclick="CargarListaRecibos()" class="btn_cargar">APLICAR FILTROS</button>
      </div>



      <table id="tabla-ventas" class="display" style=" z-index: 1;">
        <thead>
          <tr>
            <th>Vendedor</th>
            <th>Semana</th>
            <th>Cobro Total</th>
            <th>Venta Total</th>
          </tr>
        </thead>
        <tbody id="cuerpoTabla">
          <!-- Se llenará dinámicamente con JavaScript -->
        </tbody>
        <tfoot>
          <tr>
            <th colspan="2">Totales:</th>
            <th id="total-cobro"></th>
            <th id="total-venta"></th>
          </tr>
        </tfoot>
      </table>
    </div>

        <!-- --- -->
    <div class="contenedor_grafica" style="margin-top: 40px; width: 90%;">
      <button class="exp_pdf" id="exportPDF">PDF</button>
      <div class="grafica">
      <canvas id="cobrosVentasChart" style="height: 100% !important;"></canvas>
      </div>
    </div>

  </main>
  <script src="../../js/sidebar.js"></script>

  <!-- Data Table -->
  <script src="../../js/jquery-3.7.1.js"></script>
  <script src="../../js/datatables.js"></script>
  <script src="../../js/datatables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <!-- JSZip para exportar a Excel -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <!-- pdfmake para exportar a PDF -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <!-- Botones de exportación -->
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>


  <script>
    let cobrosVentasChart; // Variable para almacenar la instancia del gráfico

    
    // Inicializar DataTable
    const table = $("#tabla-ventas").DataTable({
      paging: false, // Desactivar paginación
      scrollCollapse: true, // Permitir que colapse si el contenido es menor
      scrollX: true,
      scrollY: '400px', // Altura fija del scroll
      autoWidth: false, // Prevenir anchos incorrectos
      responsive: true,
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
      },
      dom: 'Bfrtip',
      buttons: [{
        extend: 'excelHtml5',
        text: 'Exportar a Excel',
        title: 'RESUMEN COBROS Y VENTAS',
        exportOptions: {
          columns: ':visible'
        },
        customizeData: function(data) {
          // Agregar totales al exportar
          const totalCobro = table.column(2, {
            filter: 'applied'
          }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
          const totalVenta = table.column(3, {
            filter: 'applied'
          }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

          const totalRow = ['', 'Totales:', totalCobro.toFixed(2), totalVenta.toFixed(2)];
          data.body.push(totalRow);
        }
      }],
      columnDefs: [{
          // Formatea la columna "Total Cobro" (índice 2)
          targets: 2,
          render: function(data, type, row) {
            if (type === 'display' || type === 'filter') {
              return new Intl.NumberFormat('es-GT', {
                style: 'currency',
                currency: 'GTQ'
              }).format(data);
            }
            return data; // Devuelve el número original para cálculos
          }
        },
        {
          // Formatea la columna "Total Venta" (índice 3)
          targets: 3,
          render: function(data, type, row) {
            if (type === 'display' || type === 'filter') {
              return new Intl.NumberFormat('es-GT', {
                style: 'currency',
                currency: 'GTQ'
              }).format(data);
            }
            return data; // Devuelve el número original para cálculos
          }
        }
      ]
    });

    function CargarListaRecibos() {
      let campoFechaInicio = document.getElementById("fecha_inicio").value;
      let campoFechaFinal = document.getElementById("fecha_final").value;
      const sucursal = document.getElementById("region");
      const idSucursal = sucursal.value;

      // let datos;
      $.ajax({
        url: "cv.data.php",
        dataType: 'json',
        type: "post",
        data: {
          'fechaInicio': campoFechaInicio,
          'fechaFinal': campoFechaFinal,
          'sucursal':idSucursal
        },
        success: function(object) {
          //const datos = object;
          // const datos = JSON.stringify(object);             
          // datosInput.value = object;  
          table.clear();
          // Extraer datos para la gráfica
          const labels = [];
          const dataCobro = [];
          const dataVenta = [];

          object.forEach(fila => {
            table.row.add([
              fila.nombre_vendedor,
              fila.semana,
              parseFloat(fila.total_cobro).toFixed(2),
              parseFloat(fila.total_venta).toFixed(2)
            ]);

             // Datos para el gráfico
            labels.push(fila.nombre_vendedor);
            dataCobro.push(parseFloat(fila.total_cobro));
            dataVenta.push(parseFloat(fila.total_venta));

          });

          // Dibuja la tabla nuevamente con los datos actualizados
          table.draw();

        // Actualizar la gráfica con los nuevos datos
          updateChart(labels, dataCobro, dataVenta);

        },
        error: function(jqXHR, textStatus, errorThrown) {
          console.log("Status: " + textStatus);
          console.log("Error: " + errorThrown);
          alertify.error("Ocurrió un error al guardar el registro");
        },
      });
      // Ajustar las columnas después de inicializar
      table.columns.adjust();
    }
    // Función para calcular y actualizar los totales
    function calcularTotales() {
      // Calcula los totales
      const totalCobro = table.column(2, {
        filter: 'applied'
      }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
      const totalVenta = table.column(3, {
        filter: 'applied'
      }).data().reduce((a, b) => parseFloat(a) + parseFloat(b), 0);

      // Aplica formato de moneda quetzal
      const formatoMoneda = new Intl.NumberFormat('es-GT', {
        style: 'currency',
        currency: 'GTQ'
      });

      // Actualiza el footer con los totales formateados
      $('#total-cobro').html(formatoMoneda.format(totalCobro));
      $('#total-venta').html(formatoMoneda.format(totalVenta));
    }


    // Ajusta columnas cuando se redibuje la tabla
    table.on('draw', function() {
      calcularTotales();
    });

// --- Configuración y actualización de la gráfica ---

  function initChart() {
    const ctx = document.getElementById('cobrosVentasChart').getContext('2d');
    cobrosVentasChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: [],
        datasets: [{
          label: 'Total Cobrado',
          data: [],
          backgroundColor: 'rgba(75, 192, 192, 0.6)',
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 1
        }, {
          label: 'Total Vendido',
          data: [],
          backgroundColor: 'rgba(153, 102, 255, 0.6)',
          borderColor: 'rgba(153, 102, 255, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        // Set maintainAspectRatio to false but ensure parent container has a height.
        // Or set to true and manage canvas sizing via CSS/parent.
        maintainAspectRatio: false, 
        scales: {
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Monto (GTQ)'
            },
            ticks: {
              callback: function(value, index, values) {
                return new Intl.NumberFormat('es-GT', {
                  style: 'currency',
                  currency: 'GTQ'
                }).format(value);
              }
            }
          },
          x: {
            title: {
              display: true,
              text: 'Vendedor'
            }
          }
        },
        plugins: {
          title: {
            display: true,
            text: 'Cobros y Ventas por Vendedor'
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.parsed.y !== null) {
                  label += new Intl.NumberFormat('es-GT', {
                    style: 'currency',
                    currency: 'GTQ'
                  }).format(context.parsed.y);
                }
                return label;
              }
            }
          }
        }
      }
    });
  }

  // Función para actualizar los datos de la gráfica
  function updateChart(labels, dataCobro, dataVenta) {
    // Destroy the old chart instance before creating a new one if it exists
    if (cobrosVentasChart) {
        cobrosVentasChart.destroy();
    }
    initChart(); // Re-initialize the chart with the new data structure

    cobrosVentasChart.data.labels = labels;
    cobrosVentasChart.data.datasets[0].data = dataCobro;
    cobrosVentasChart.data.datasets[1].data = dataVenta;
    cobrosVentasChart.update();
  }

  // Initial setup when the page loads
  document.addEventListener('DOMContentLoaded', function() {
    cargarFechas(); // Set initial dates
    initChart(); // Initialize chart structure
    // Optionally, call CargarListaRecibos() here to load initial data for both table and chart
    // CargarListaRecibos();
  });
  // Exportar a PDF
document.getElementById('exportPDF').addEventListener('click', async () => {
    const { jsPDF } = window.jspdf || window.jspdf;


  // Usa la instancia correcta del gráfico
  const imgData = cobrosVentasChart.toBase64Image();

  // Crea el PDF y agrega la imagen
  const pdf = new jsPDF();
  const pdfWidth = 210; // A4 horizontal size (mm)
  const imgWidth = 180; // Tu gráfico
  const marginX = (pdfWidth - imgWidth) / 2;
  pdf.addImage(imgData, 'PNG', marginX, 20, imgWidth, 100);
  pdf.save('graficaSaldos.pdf'); // Nombre del archivo
});

  </script>

  <script>
    function cargarFechas() {
      var fecha = new Date(); //Fecha actual
      var mes = fecha.getMonth() + 1; //obteniendo mes
      var dia = fecha.getDate(); //obteniendo dia
      var ano = fecha.getFullYear(); //obteniendo año
      if (dia < 10)
        dia = '0' + dia; //agrega cero si el menor de 10
      if (mes < 10)
        mes = '0' + mes //agrega cero si el menor de 10
      document.getElementById('fecha_inicio').value = ano + "-" + mes + "-" + dia;
      document.getElementById('fecha_final').value = ano + "-" + mes + "-" + dia;
    }
  </script>

  <!-- Creación de Grafica -->
  <!-- Chart.js -->
  <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->


</body>

</html>