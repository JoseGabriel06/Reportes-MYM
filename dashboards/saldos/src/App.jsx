import { useEffect, useState } from "react";
import { Sidebar } from './Sidebar';
import { Navbar } from './Navbar';
import { Contenedor } from './Contenedor';
import { Card } from './Card';
import { Filtros } from './Filtros';
import { Filtro } from './Filtro';
import { TCGCard } from "./TCGCard";

// Función para formatear números a moneda GTQ
const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-GT', {
    style: 'currency',
    currency: 'GTQ',
  }).format(value);
};



function App() {
  const [data, setData] = useState(null);
  const [sucursal, setSucursal] = useState("central"); // Estado de sucursal

  // useEffect para realizar la solicitud cuando cambie "sucursal"
  useEffect(() => {
    const fetchData = async () => {
      try {
        const response = await fetch('http://localhost/reportes/dashboards/saldos/public/consulta_saldo.php?sucursal='+sucursal);
        if (!response.ok) {
          throw new Error('Error al obtener los datos');
        }
        const result = await response.json();
        setData(result); // Actualizamos los datos recibidos
      } catch (error) {
        console.error('Hubo un problema con la solicitud:', error);
      }
    };

    fetchData(); // Llamamos a la función
  }, [sucursal]); // Solo se ejecuta cuando cambia "sucursal"

  // Manejador para actualizar la sucursal seleccionada
  const manejarCambioSucursal = (nuevaSucursal) => {
    setSucursal(nuevaSucursal); // Actualizamos el estado de sucursal
  };

  return (
    <>
      <Navbar />
      <Sidebar />
      <Contenedor 
      filtros={
      <Filtros 
      ancho={'100%'} 
      icono={'bx-filter'}>
        <Filtro 
        identificador={'sucursal'} 
        nombre={'Sucursal'} 
        onChange={manejarCambioSucursal} // Pasamos el manejador
        />
      </Filtros>
      }>
        <Card
          identificador={'saldoTotal'}
          ancho={'38%'}
          alto={'220px'}
          titulo={'Saldo total'}
          monto={data ? formatCurrency(data.saldo) : "Cargando..."}
          icono={'bx-money'}
        />
        <TCGCard
          ancho={'60%'}
          alto={'220px'}
          titulo={'Saldo por año'}
          icono={'bx-money'}
          idGrafica={'graficaXAnio'}
          tipoGrafica={'doughnut'}
          direccionPHP={'http://localhost/reportes/dashboards/saldos/public/consulta_saldo_anual.php'}
          columna={'anio'}
          nombreColumna={'Año'}
          alturaGrafica={'115px'}
        />
        <TCGCard
          ancho={'100%'}
          alto={'290px'}
          titulo={'Saldo por vendedor'}
          icono={'bx-money'}
          idGrafica={'graficaXVendedores'}
          tipoGrafica={'bar'}
          direccionPHP={'http://localhost/reportes/dashboards/saldos/public/consulta_saldo_x_vendedor.php'}
          columna={'vendedor'}
          nombreColumna={'Vendedor'}
          alturaGrafica={'180px'}
        />
      </Contenedor>
    </>
  );
}

export default App;
