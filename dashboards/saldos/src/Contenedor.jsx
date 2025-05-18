import './css/contenedor.css'

function Contenedor({filtros,children}){
    return(
        <div className='contenedor_principal'>
      <div className='contenedor_dashboard'>
      <h2 className='titulo_dashboard'>VENTAS ANUALES</h2>
        <div className='ctnd_filtros'>
          {filtros}
        </div>
        <div className='cuadro'>
          {children}
        </div>
      </div>
    </div>
    );
}

export {Contenedor}