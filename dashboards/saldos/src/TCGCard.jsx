import React, { useEffect, useRef } from 'react';
import './css/TCGcard.css';

function TCGCard(props) {
  const chartRef = useRef(null); // Referencia para la gráfica
  const tableRef = useRef(null); // Referencia para la tabla

  useEffect(() => {
    const fetchDataAndRenderChart = async () => {
      try {
        // Obtener los datos desde el servidor
        const response = await fetch(
          props.direccionPHP
        );
        if (!response.ok) {
          throw new Error('Error al obtener los datos');
        }
        const data = await response.json();

        // Verificar si los datos tienen el formato esperado
        if (data.error) {
          console.error('Error en los datos:', data.error);
          return;
        }

        // Procesar los datos para Chart.js
        const labels = data.map((item) => item[props.columna]); // Nombre del orden
        const valores = data.map((item) => item.saldo); // Saldos

        // Destruir el gráfico existente si existe
        if (chartRef.current) {
          chartRef.current.destroy();
        }

        // Crear la gráfica con Chart.js
        const ctx = document.querySelector('#'+ props.idGrafica).getContext('2d');
        chartRef.current = new Chart(ctx, {
          type: props.tipoGrafica,
          data: {
            labels,
            datasets: [
              {
                label: 'Saldo',
                data: valores,
                backgroundColor: [
                  '#FF6384',
                  '#36A2EB',
                  '#FFCE56',
                  '#4BC0C0',
                  '#9966FF',
                  '#FF9F40',
                ],
                borderColor: '#ccc',
                borderWidth: 1,
              },
            ],
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                position: 'top',
              },
              tooltip: {
                enabled: true, // Permite mostrar información al pasar el cursor
              },
            },
            scales: {
              x: {
                ticks: {
                  display: false, // Oculta las etiquetas del eje X
                },
                grid: {
                  drawBorder: false, // Opcional: Oculta la línea del eje X
                  display: false, // Opcional: Oculta las líneas de la cuadrícula
                },
              },
              y: {
                ticks: {
                  display: false,
                },
              },
            },
          },
        });
        

        // Inicializar DataTables
        if ($.fn.DataTable.isDataTable(tableRef.current)) {
          $(tableRef.current).DataTable().destroy(); // Destruir DataTable existente
        }

        $(tableRef.current).DataTable({
          data: data,
          columns: [
            { data: props.columna, title: props.nombreColumna },
            { 
              data: 'saldo', 
              title: 'Saldo',
              render: (data) => `${parseFloat(data).toLocaleString('es-GT', {
                style: 'currency',
                currency: 'GTQ',
                minimumFractionDigits: 2,
              })}`, // Formato de moneda en Quetzales
            },
          ],
          dom: 't', // Solo muestra la tabla (sin búsqueda, paginación, ni conteo)
          ordering: false, // Deshabilitar la ordenación
          language: {
            emptyTable: 'No hay datos disponibles', // Mensaje si no hay datos
          },
          scrollY: props.alturaGrafica, // Altura máxima para la tabla (ajusta según necesites)
          scrollCollapse: true, // Habilitar colapso del scroll si hay pocos datos
        });
      } catch (error) {
        console.error('Error al cargar los datos o renderizar la gráfica:', error);
      }
    };

    fetchDataAndRenderChart();

    // Cleanup: destruir el gráfico y la tabla al desmontar el componente
    return () => {
      if (chartRef.current) {
        chartRef.current.destroy();
      }
      if ($.fn.DataTable.isDataTable(tableRef.current)) {
        $(tableRef.current).DataTable().destroy();
      }
    };
  }, []);

  return (
    <div
      className="contenedor_card"
      style={{
        width: props.ancho,
        height: props.alto,
      }}
    >
      <h3 className="titulo_card">{props.titulo}</h3>
      <i className={'bx ' + props.icono + ' icono_card'}></i>
      <div className="tcg_contenedor_tabla">
        <table ref={tableRef} className="display"></table>
      </div>
      <div className="tcg_contenedor_grafica">
        <canvas id={props.idGrafica}></canvas>
      </div>
    </div>
  );
}

export { TCGCard };
