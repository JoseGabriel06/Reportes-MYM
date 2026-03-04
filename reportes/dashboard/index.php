<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../../login/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Ejecutivo - RDX</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

    <style>
        body {
            background: #f4f6f9;
        }

        .card-kpi {
            border-radius: 18px;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
        }

        .card-kpi h2 {
            font-weight: bold;
        }

        .chart-card {
            border-radius: 18px;
        }

        .header-title {
            font-weight: 600;
        }

        .table-card {
            border-radius: 18px;
        }

        .chart-card {
            border-radius: 18px;
            margin-bottom: 80px;
            padding-bottom: 30px;
        }

        #doughnutPago {
            max-width: 280px;
            max-height: 280px;
            margin: 0 auto;
        }

        .doughnut-wrapper {
            height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>

    <div class="container-fluid p-4">

        <h2 class="header-title mb-4">📊 Dashboard Ejecutivo - Refrigerantes (RDX)</h2>

        <!-- FILTROS -->
        <div class="row mb-4">
            <div class="col-md-3">
                <select id="sucursal" class="form-select">
                    <option value="0">Todas las sucursales</option>
                    <option value="1">Central</option>
                    <option value="2">Petén</option>
                    <option value="3">Xela</option>
                </select>
            </div>

            <div class="col-md-3">
                <input type="date" id="fechaInicio" class="form-control">
            </div>

            <div class="col-md-3">
                <input type="date" id="fechaFinal" class="form-control">
            </div>

            <div class="col-md-3">
                <button class="btn btn-primary w-100" onclick="cargar()">Actualizar</button>
            </div>
        </div>

        <!-- KPIs -->
        <div class="row mb-4 text-center">

            <div class="col-md-3">
                <div class="card card-kpi p-3 shadow">
                    <h6 id="tituloTotal">Total Empresa</h6>
                    <h2 id="totalEmpresa">Q0</h2>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-kpi p-3 shadow">
                    <h6>Total Contado</h6>
                    <h2 id="totalContado">Q0</h2>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-kpi p-3 shadow">
                    <h6>Total Crédito</h6>
                    <h2 id="totalCredito">Q0</h2>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-kpi p-3 shadow">
                    <h6>% Contado</h6>
                    <h2 id="porcentajeContado">0%</h2>
                </div>
            </div>

            <div class="col-md-3 mt-3">
                <div class="card card-kpi p-3 shadow">
                    <h6>Variación vs Mes Anterior</h6>
                    <h2 id="variacionMes">0%</h2>
                </div>
            </div>

            <div class="col-md-3 mt-3">
                <div class="card card-kpi p-3 shadow">
                    <h6>Proyección Mensual</h6>
                    <h2 id="proyeccionMensual">Q0</h2>
                </div>
            </div>

            <div class="col-md-3 mt-3">
                <div class="card card-kpi p-3 shadow">
                    <h6>Producto más riesgoso</h6>
                    <h6 id="productoRiesgoso">-</h6>
                </div>
            </div>

        </div>

        <div class="row">

            <div class="col-md-6">
                <div class="card chart-card shadow p-3">
                    <h6 class="text-center">Comparativo por Sucursal</h6>
                    <canvas id="barSucursales"></canvas>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card chart-card shadow p-3">
                    <h6 class="text-center">Contado vs Crédito</h6>
                    <div class="doughnut-wrapper">
                        <canvas id="doughnutPago"></canvas>
                    </div>
                </div>
            </div>

        </div>

        <!-- TABLA POR CODIGO -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card table-card shadow p-3">
                    <h6 class="text-center mb-3">Detalle por Código</h6>
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-4">
                            <input type="text" id="filtroCodigo" class="form-control"
                                placeholder="Filtrar por código o nombre..."
                                onkeyup="filtrarTabla()">
                        </div>

                        <div class="col-md-8 text-end">
                            <button class="btn btn-success me-2" onclick="exportarExcel()">
                                Exportar Excel
                            </button>

                            <button class="btn btn-danger" onclick="exportarPDF()">
                                Exportar PDF
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Total</th>
                                    <th>Contado</th>
                                    <th>Crédito</th>
                                    <th>% Crédito</th>
                                    <th>Margen</th>
                                    <th>% Margen</th>
                                    <th>Riesgo</th>
                                </tr>
                            </thead>
                            <tbody id="tablaProductos">
                                <tr>
                                    <td colspan="10" class="text-center">Seleccione filtros y presione actualizar</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        function calcularNivelRiesgo(porcentajeCredito, porcentajeMargen) {

            let score = porcentajeCredito - porcentajeMargen;

            if (score > 40) return {
                nivel: "ALTO",
                color: "#dc3545"
            };
            if (score > 15) return {
                nivel: "MEDIO",
                color: "#ffc107"
            };
            return {
                nivel: "BAJO",
                color: "#28a745"
            };
        }

        let barSucursales, doughnutPago;

        function cargar() {

            let data = new URLSearchParams();
            data.append("fechaInicio", document.getElementById('fechaInicio').value);
            data.append("fechaFinal", document.getElementById('fechaFinal').value);
            data.append("sucursal", document.getElementById('sucursal').value);

            fetch('rdx_dashboard_data.php', {
                    method: 'POST',
                    body: data
                })
                .then(res => res.json())
                .then(resp => {

                    let titulo = document.getElementById("tituloTotal");

                    if (resp.modo === "consolidado") {
                        titulo.innerText = "Total Empresa";
                    } else {
                        titulo.innerText = "Total Sucursal";
                    }
                    document.getElementById('totalEmpresa').innerText =
                        "Q" + Number(resp.total_empresa ?? resp.total).toLocaleString();

                    document.getElementById('totalContado').innerText =
                        "Q" + Number(resp.contado).toLocaleString();

                    document.getElementById('totalCredito').innerText =
                        "Q" + Number(resp.credito).toLocaleString();

                    document.getElementById('porcentajeContado').innerText =
                        resp.porcentaje_contado + "%";

                    let variacion = resp.variacion_porcentual ?? 0;

                    let variacionElemento = document.getElementById("variacionMes");
                    variacionElemento.innerText = variacion + "%";

                    if (variacion > 0) {
                        variacionElemento.style.color = "#28a745";
                    } else if (variacion < 0) {
                        variacionElemento.style.color = "#dc3545";
                    } else {
                        variacionElemento.style.color = "#ffffff";
                    }

                    document.getElementById("proyeccionMensual").innerText =
                        "Q" + Number(resp.proyeccion_mensual ?? 0).toLocaleString();

                    if (doughnutPago) doughnutPago.destroy();

                    doughnutPago = new Chart(document.getElementById('doughnutPago'), {
                        type: 'doughnut',
                        data: {
                            labels: ['Contado', 'Crédito'],
                            datasets: [{
                                data: [resp.contado, resp.credito],
                                backgroundColor: ['#28a745', '#dc3545']
                            }]
                        }
                    });

                    if (resp.modo === "consolidado") {

                        let labels = Object.keys(resp.totales);
                        let values = Object.values(resp.totales);

                        if (barSucursales) barSucursales.destroy();

                        barSucursales = new Chart(document.getElementById('barSucursales'), {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Total por sucursal',
                                    data: values,
                                    backgroundColor: ['#1e3c72', '#232528', '#0d6efd']
                                }]
                            }
                        });

                        document.getElementById("tablaProductos").innerHTML =
                            "<tr><td colspan='10' class='text-center'>Seleccione una sucursal para ver detalle por código</td></tr>";

                    } else {

                        if (barSucursales) barSucursales.destroy();

                        let html = "";

                        resp.productos.sort((a, b) => b.total_credito - a.total_credito);

                        let productoRiesgoso = null;
                        let peorIndice = -999;

                        resp.productos.forEach(p => {

                            let margen = p.total_general - (p.cantidad * p.costo);

                            let porcentajeMargen = p.total_general > 0 ?
                                ((margen / p.total_general) * 100) : 0;

                            let porcentajeCredito = p.total_general > 0 ?
                                ((p.total_credito / p.total_general) * 100) : 0;

                            let indiceRiesgo = porcentajeCredito - porcentajeMargen;

                            if (indiceRiesgo > peorIndice) {
                                peorIndice = indiceRiesgo;
                                productoRiesgoso = p.nombre;
                            }

                            let riesgo = calcularNivelRiesgo(porcentajeCredito, porcentajeMargen);
                            let claseFila = "";

                            if (riesgo.nivel === "ALTO") claseFila = "table-danger";
                            if (riesgo.nivel === "MEDIO") claseFila = "table-warning";

                            if (porcentajeCredito > 60 && porcentajeMargen < 15) {
                                claseFila = "table-danger";
                            }

                            html += `
                                    <tr class="${claseFila}">
                                    <td>${p.codigormym}</td>
                                    <td>${p.nombre}</td>
                                    <td>${Number(p.cantidad).toLocaleString()}</td>
                                    <td>Q${Number(p.total_general).toLocaleString()}</td>
                                    <td class="text-success">Q${Number(p.total_contado).toLocaleString()}</td>
                                    <td class="text-danger">Q${Number(p.total_credito).toLocaleString()}</td>
                                    <td>${porcentajeCredito.toFixed(2)}%</td>
                                    <td class="${margen>=0 ? 'text-success':'text-danger'}">
                                    Q${Number(margen).toLocaleString()}
                                    </td>
                                    <td class="${
                                        porcentajeMargen >= 25 ? 'text-success' :
                                        porcentajeMargen >= 10 ? 'text-warning' :
                                        'text-danger'
                                    }">
                                    ${porcentajeMargen.toFixed(2)}%
                                    </td>
                                    <td style="color:${riesgo.color}; font-weight:bold">
                                    ${riesgo.nivel}
                                    </td>
                                    </tr>
                                    `;
                        });

                        document.getElementById("tablaProductos").innerHTML = html;

                        if (productoRiesgoso) {
                            document.getElementById("productoRiesgoso").innerText = productoRiesgoso;
                        }
                    }

                });
        }

        function exportarExcel() {

            let tabla = document.querySelector("table");

            let wb = XLSX.utils.table_to_book(tabla, {
                sheet: "RDX"
            });

            let fechaInicio = document.getElementById('fechaInicio').value;
            let fechaFinal = document.getElementById('fechaFinal').value;

            XLSX.writeFile(wb, `VENTA_RADIEX_${fechaInicio}_AL_${fechaFinal}.xlsx`);
        }

        async function exportarPDF() {

            const {
                jsPDF
            } = window.jspdf;
            let pdf = new jsPDF('l', 'mm', 'a4');

            let fechaInicio = document.getElementById('fechaInicio').value;
            let fechaFinal = document.getElementById('fechaFinal').value;

            let totalEmpresa = document.getElementById("totalEmpresa").innerText;
            let totalContado = document.getElementById("totalContado").innerText;
            let totalCredito = document.getElementById("totalCredito").innerText;
            let porcentajeContado = document.getElementById("porcentajeContado").innerText;
            let productoRiesgoso = document.getElementById("productoRiesgoso").innerText;

            // =========================
            // LOGO
            // =========================
            let logo = new Image();
            logo.src = "assets/logo_mym.jpg";

            await new Promise(resolve => logo.onload = resolve);

            pdf.addImage(logo, 'PNG', 14, 8, 40, 25);

            // =========================
            // TITULO
            // =========================
            pdf.setFontSize(16);
            pdf.setFont("helvetica", "bold");
            pdf.text("INFORME FINANCIERO - VENTA DE REFRIGERANTES RADIEX", 60, 15);

            pdf.setFontSize(11);
            pdf.setFont("helvetica", "normal");
            pdf.text(`Período Evaluado: ${fechaInicio} al ${fechaFinal}`, 60, 22);

            pdf.line(14, 35, 280, 35);

            let canvas = document.getElementById("doughnutPago");
            let chartImage = canvas.toDataURL("image/png", 1.0);
            pdf.addImage(chartImage, "PNG", 190, 45, 45, 35);

            // =========================
            // RESUMEN EJECUTIVO
            // =========================
            pdf.setFontSize(12);
            pdf.setFont("helvetica", "bold");
            pdf.text("Resumen Ejecutivo", 14, 45);

            pdf.setFont("helvetica", "normal");
            pdf.setFontSize(10);

            pdf.text(`Total Facturado: ${totalEmpresa}`, 14, 52);
            pdf.text(`Total Contado: ${totalContado}`, 14, 58);
            pdf.text(`Total Crédito: ${totalCredito}`, 14, 64);
            pdf.text(`% Contado: ${porcentajeContado}`, 14, 70);
            pdf.text(`Producto con Mayor Riesgo Financiero: ${productoRiesgoso}`, 14, 76);

            // =========================
            // TABLA
            // =========================
            let table = document.querySelector("table");

            pdf.autoTable({
                html: table,
                startY: 95,
                margin: {
                    bottom: 40
                }, // 🔥 RESERVA ESPACIO INFERIOR
                styles: {
                    fontSize: 8
                },
                headStyles: {
                    fillColor: [25, 45, 85]
                },
                theme: 'grid',
                columnStyles: {
                    1: {
                        cellWidth: 65
                    }
                }
            });

            let finalY = pdf.lastAutoTable.finalY + 10;

            // =========================
            // TOTALES FINALES
            // =========================
            let pageHeight = pdf.internal.pageSize.height;

            pdf.setFont("helvetica", "bold");
            pdf.setFontSize(11);
            pdf.text("Totales Generales del Período", 14, finalY);

            pdf.setFont("helvetica", "normal");
            pdf.setFontSize(10);
            pdf.text(`Total General Facturado: ${totalEmpresa}`, 14, finalY + 7);
            pdf.text(`Total Crédito: ${totalCredito}`, 14, finalY + 13);

            // =========================
            // FOOTER GLOBAL
            // =========================

            let totalPages = pdf.internal.getNumberOfPages();

            for (let i = 1; i <= totalPages; i++) {
                pdf.setPage(i);

                pdf.setFontSize(8);

                pdf.text(
                    "Página " + i + " de " + totalPages,
                    pdf.internal.pageSize.width - 30,
                    pdf.internal.pageSize.height - 10
                );
            }

            pdf.save("Informe_Financiero_RADIEX.pdf");
        }

        function filtrarTabla() {

            let input = document.getElementById("filtroCodigo").value.toLowerCase();
            let filas = document.querySelectorAll("#tablaProductos tr");

            filas.forEach(fila => {

                let texto = fila.innerText.toLowerCase();

                if (texto.includes(input)) {
                    fila.style.display = "";
                } else {
                    fila.style.display = "none";
                }

            });
        }

        let hoy = new Date();
        let primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);

        document.getElementById("fechaInicio").value =
            primerDia.toISOString().split('T')[0];

        document.getElementById("fechaFinal").value =
            hoy.toISOString().split('T')[0];
    </script>

</body>

</html>