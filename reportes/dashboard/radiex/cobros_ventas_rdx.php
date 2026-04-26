<?php
date_default_timezone_set('America/Guatemala');
$primerDiaMes = date('Y-m-01');
$hoy = date('Y-m-d');
?>

<!DOCTYPE html>
<html>

<head>
    <title>Resumen Cobros y Ventas Radiex</title>

    <link rel="stylesheet"
        href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <link rel="stylesheet"
        href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: Roboto, Arial;
            background: #f5f7fb;
            padding: 25px;
            margin: 0;
        }

        .header-box {
            background: #fff;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .08);
            margin-bottom: 20px;
        }

        .titulo {
            font-size: 34px;
            font-weight: 700;
            color: #16355c;
        }

        .filtros {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
            align-items: center;
        }

        input,
        select {
            padding: 11px 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
        }

        .btn {
            background: #16355c;
            color: white;
            border: none;
            padding: 11px 22px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn:hover {
            background: #214d83;
        }

        .kpis {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .kpi {
            background: #eef4ff;
            padding: 18px 25px;
            border-radius: 12px;
            min-width: 260px;
            font-weight: 700;
            font-size: 22px;
            color: #16355c;
        }

        .kpi small {
            display: block;
            font-size: 15px;
            margin-bottom: 6px;
        }

        tfoot {
            font-weight: bold;
            background: #eef4ff;
        }

        table.dataTable tbody tr:hover {
            background: #eef5ff;
        }

        #grafica {
            margin-top: 50px;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .08);
        }
    </style>

</head>

<body>

    <div class="header-box">

        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div class="titulo">
                Resumen Cobros y Ventas Radiex
            </div>

            <a href="../../../index.php">
                <button class="btn">
                    ← Menú Principal
                </button>
            </a>
        </div>


        <div class="filtros">

            <select id="sucursal">
                <option value="1">Central</option>
                <option value="2">Petén</option>
                <option value="3">Xela</option>
            </select>

            <input
                type="date"
                id="fechaInicio"
                value="<?= $primerDiaMes ?>">

            <input
                type="date"
                id="fechaFinal"
                value="<?= $hoy ?>">

            <button id="buscar" class="btn">
                Aplicar filtros
            </button>

        </div>


        <div class="kpis">

            <div class="kpi">
                <small>Total contado</small>
                Q <span id="totalContado">0.00</span>
            </div>

            <div class="kpi">
                <small>Total crédito</small>
                Q <span id="totalCredito">0.00</span>
            </div>

            <div class="kpi">
                <small>Total general</small>
                Q <span id="totalGeneral">0.00</span>
            </div>

        </div>

    </div>



    <table id="tabla" class="display">

        <thead>
            <tr>
                <th>Vendedor</th>
                <th>Contado</th>
                <th>Crédito</th>
                <th>Total General</th>
            </tr>
        </thead>

        <tfoot>
            <tr>
                <th>Totales</th>
                <th id="footContado"></th>
                <th id="footCredito"></th>
                <th id="footGeneral"></th>
            </tr>
        </tfoot>

    </table>


    <div id="grafica">
        <canvas id="chart"></canvas>
    </div>



    <script>
        let grafica = null;



        function formato(v) {
            return parseFloat(v).toLocaleString(
                undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );
        }



        $("#buscar").click(function() {

            $('#tabla').DataTable({

                destroy: true,

                ajax: {
                    url: 'get_cobros_ventas_rdx.php',
                    type: 'POST',

                    data: function(d) {

                        d.sucursal = $("#sucursal").val();
                        d.fechaInicio = $("#fechaInicio").val();
                        d.fechaFinal = $("#fechaFinal").val();

                    },

                    dataSrc: function(json) {

                        $("#totalContado").text(
                            formato(json.totalContado)
                        );

                        $("#totalCredito").text(
                            formato(json.totalCredito)
                        );

                        $("#totalGeneral").text(
                            formato(json.totalGeneral)
                        );


                        $("#footContado").html(
                            'Q ' + formato(
                                json.totalContado
                            )
                        );

                        $("#footCredito").html(
                            'Q ' + formato(
                                json.totalCredito
                            )
                        );

                        $("#footGeneral").html(
                            'Q ' + formato(
                                json.totalGeneral
                            )
                        );

                        cargarGrafica(
                            json.data
                        );

                        return json.data;

                    }

                },

                dom: 'Bfrtip',

                buttons: [
                    'excelHtml5',
                    'pdfHtml5'
                ],


                columns: [

                    {
                        data: 'vendedor'
                    },

                    {
                        data: 'total_contado',
                        render: function(data) {
                            return 'Q ' + formato(data);
                        }
                    },

                    {
                        data: 'total_credito',
                        render: function(data) {
                            return 'Q ' + formato(data);
                        }
                    },

                    {
                        data: 'total_general',
                        render: function(data) {
                            return 'Q ' + formato(data);
                        }
                    }

                ]

            });

        });



        function cargarGrafica(datos) {

            let labels =
                datos.map(
                    x => x.vendedor
                );

            let contado =
                datos.map(
                    x => parseFloat(
                        x.total_contado
                    )
                );

            let credito =
                datos.map(
                    x => parseFloat(
                        x.total_credito
                    )
                );

            let total =
                datos.map(
                    x => parseFloat(
                        x.total_general
                    )
                );


            if (grafica) {
                grafica.destroy();
            }


            grafica =
                new Chart(
                    document.getElementById('chart'), {
                        type: 'bar',

                        data: {
                            labels: labels,

                            datasets: [

                                {
                                    label: 'Contado',
                                    data: contado
                                },

                                {
                                    label: 'Crédito',
                                    data: credito
                                },

                                {
                                    label: 'Total General',
                                    data: total
                                }

                            ]
                        },

                        options: {
                            responsive: true,

                            plugins: {
                                legend: {
                                    position: 'top'
                                }
                            }
                        }

                    }
                );

        }


        $("#buscar").click();
    </script>

</body>

</html>