import React, { useState, useRef } from 'react';
import './css/sidebar.css';

function Sidebar() {
  const [isSidebarClosed, setIsSidebarClosed] = useState(false);
  const [activeSubMenu, setActiveSubMenu] = useState(null);
  const sidebarRef = useRef(null);

  // Alternar un submenú específico
  const toggleSubMenu = (index) => {
    setActiveSubMenu((prev) => (prev === index ? null : index));
    if (isSidebarClosed) {
      setIsSidebarClosed(false); // Abre el sidebar si está cerrado
    }
  };

  // Renderizado
  return (
    <div ref={sidebarRef} id="sidebar" className={isSidebarClosed ? 'close' : ''}>
      <ul>
        <li>
          <span className="logo">MYM</span>
        </li>
        <hr />
        <li className="active">
          <a href="index.php">
            <i className="bx bx-home" />
            <span>Inicio</span>
          </a>
        </li>
        <li>
          <button
            onClick={() => toggleSubMenu(0)} // Índice del submenú
            className={`dropdown-btn ${activeSubMenu === 0 ? 'rotate' : ''}`}
          >
            <i className="bx bxs-report" />
            <span className="texto_menu">Reporte</span>
            <i className="bx bx-chevron-down" />
          </button>
          <ul className={`sub-menu ${activeSubMenu === 0 ? 'show' : ''}`}>
            <div>
              <li>
                <a href="reportes/ventas">Saldos</a>
              </li>
              <li>
                <a href="reportes/recibos">Recibos</a>
              </li>
              <li>
                <a href="reportes/cobrosVentas">Resumen CV</a>
              </li>
            </div>
          </ul>
        </li>
        <li className="log_out">
          <a href="login/logout.php">
            <i className="bx bx-log-out" />
            <span>Cerrar Sesión</span>
          </a>
        </li>
      </ul>
    </div>
  );
}

export { Sidebar };
