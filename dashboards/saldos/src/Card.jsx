import './css/card.css'

function Card(props) {

  return (
    <div className="contenedor_card" style={{
      width: props.ancho,
      height: props.alto,
    }}>
      <h3 className="titulo_card">{props.titulo}</h3>
      <h2 className="info_card" id={props.identificador}>{props.monto}</h2>
      <i className={'bx ' + props.icono +' icono_card'}></i>
    </div>
  );
}

export { Card };