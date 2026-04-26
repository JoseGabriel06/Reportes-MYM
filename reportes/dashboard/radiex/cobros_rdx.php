<?php
date_default_timezone_set('America/Guatemala');
$primerDiaMes = date('Y-m-01');
$hoy = date('Y-m-d');
?>

<!DOCTYPE html>
<html>

<head>

    <title>Cobros Radiex</title>

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
            font-family: Roboto;
            padding: 25px;
            background: #f5f7fb;
        }

        .header-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .08);
        }

        .titulo {
            font-size: 30px;
            font-weight: 700;
            color: #16355c;
        }

        .filtros {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        input,
        select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .btn {
            background: #16355c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
        }

        .kpis {
            display: flex;
            gap: 20px;
            margin: 25px 0;
        }

        .kpi {
            background: #eef4ff;
            padding: 18px;
            border-radius: 10px;
            min-width: 250px;
            font-weight: bold;
            font-size: 24px;
        }

        tfoot {
            font-weight: bold;
            background: #eef4ff;
        }

        #grafica {
            margin-top: 40px;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .08);
        }
    </style>

</head>

<body>

    <div class="header-box">

        <div style="display:flex;justify-content:space-between">

            <div class="titulo">
                Cobros Radiex por Vendedor
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

            <button id="buscar"
                class="btn">
                Consultar
            </button>

        </div>


        <div class="kpis">

            <div class="kpi">
                Cobrado:
                Q <span id="totalCobrado">0</span>
            </div>

        </div>

    </div>


    <table
        id="tabla"
        class="display">

        <thead>
            <tr>
                <th>Vendedor</th>
                <th>Total Cobrado</th>
                <th>Recibos</th>
                <th>Ventas Cobradas</th>
            </tr>
        </thead>

        <tfoot>
            <tr>
                <th>TOTALES</th>
                <th id="footCobro"></th>
                <th id="footRecibos"></th>
                <th id="footVentas"></th>
            </tr>
        </tfoot>

    </table>


    <div id="grafica">
        <canvas id="chart"></canvas>
    </div>


    <script>
        let grafica = null;

        function cargarGrafica(datos) {

            let labels =
                datos.map(x => x.vendedor);

            let cobros =
                datos.map(x => parseFloat(x.total_cobrado));

            let recibos =
                datos.map(x => parseInt(x.recibos));

            if (grafica) {
                grafica.destroy();
            }

            grafica =
                new Chart(
                    document.getElementById('chart'), {
                        type: 'bar',

                        data: {
                            labels: labels,

                            datasets: [{
                                    label: 'Cobrado',
                                    data: cobros
                                },
                                {
                                    label: 'Recibos',
                                    data: recibos
                                }
                            ]
                        }
                    }
                );

        }



        $("#buscar").click(function() {

            $('#tabla').DataTable({

                destroy: true,

                ajax: {
                    url: 'get_cobros_rdx.php',
                    type: 'POST',

                    data: function(d) {

                        d.sucursal =
                            $("#sucursal").val();

                        d.fechaInicio =
                            $("#fechaInicio").val();

                        d.fechaFinal =
                            $("#fechaFinal").val();

                    },

                    dataSrc: function(json) {

                        let totalRecibos = 0;
                        let totalVentas = 0;

                        json.rows.forEach(function(r) {
                            totalRecibos += parseInt(r.recibos);
                            totalVentas += parseInt(r.ventas_cobradas);
                        });

                        $("#totalCobrado").text(
                            parseFloat(
                                json.totalCobrado
                            ).toLocaleString()
                        );

                        $("#footCobro").html(
                            'Q ' +
                            parseFloat(
                                json.totalCobrado
                            ).toLocaleString()
                        );

                        $("#footRecibos").html(
                            totalRecibos
                        );

                        $("#footVentas").html(
                            totalVentas
                        );

                        cargarGrafica(
                            json.rows
                        );

                        return json.rows;

                    }

                },


                dom: 'Bfrtip',

                buttons: [

                    {
                        extend: 'excelHtml5',

                        title: 'Cobros_Radiex',

                        footer: true,

                        customize: function(xlsx) {

                            var sheet =
                                xlsx.xl.worksheets['sheet1.xml'];

                            var lastRow =
                                $('row', sheet).length + 1;

                            $('sheetData', sheet).append(

                                '<row r="' + lastRow + '">' +

                                '<c t="inlineStr" r="A' + lastRow + '">' +
                                '<is><t>TOTAL COBRADO</t></is>' +
                                '</c>' +

                                '<c t="inlineStr" r="B' + lastRow + '">' +
                                '<is><t>' +
                                $("#totalCobrado").text() +
                                '</t></is>' +
                                '</c>' +

                                '</row>'
                            );

                        }

                    },

                    {
                        extend: 'pdfHtml5',

                        title: 'Cobros Radiex por Vendedor',

                        orientation: 'landscape',

                        pageSize: 'A4',

                        footer: true,

                        customize: function(doc) {

                            doc.pageMargins = [
                                20, 40, 20, 30
                            ];

                            doc.defaultStyle.fontSize = 10;

                            doc.content[1].table.widths = [
                                120,
                                90,
                                70,
                                90
                            ];

                            doc.content.unshift({
                                text: 'Distribuidora MYM\nCobros Radiex por Vendedor',
                                alignment: 'center',
                                fontSize: 16,
                                bold: true,
                                margin: [0, 0, 0, 12]
                            });

                            doc.content.push({
                                text: '\nTOTAL COBRADO: Q ' +
                                    $("#totalCobrado").text(),
                                bold: true,
                                alignment: 'right',
                                fontSize: 11
                            });

                        }

                    }

                ],


                columns: [

                    {
                        data: 'vendedor'
                    },

                    {
                        data: 'total_cobrado',
                        render: function(data) {
                            return 'Q ' +
                                parseFloat(data)
                                .toLocaleString(
                                    undefined, {
                                        minimumFractionDigits: 2
                                    }
                                );
                        }
                    },

                    {
                        data: 'recibos'
                    },

                    {
                        data: 'ventas_cobradas'
                    }

                ]

            });

        });


        $("#buscar").click();
    </script>

</body>

</html>