const toggleButton = document.getElementById('toggle-btn');
const sidebar = document.getElementById('sidebar');

function toggleSidebar() {
  sidebar.classList.toggle('close');
  toggleButton.classList.toggle('rotate');

  closeAllSubMenus();
  closeAllSubMenus2(); // Aseguramos que los submenús de segundo nivel también se cierren
}

function toggleSubMenu(button) {
  // Cerrar todos los submenús de primer y segundo nivel si se abre uno nuevo
  if (!button.nextElementSibling.classList.contains('show')) {
    closeAllSubMenus();
    closeAllSubMenus2();
  }

  button.nextElementSibling.classList.toggle('show');
  button.classList.toggle('rotate');

  if (sidebar.classList.contains('close')) {
    sidebar.classList.toggle('close');
    toggleButton.classList.toggle('rotate');
  }
}

function closeAllSubMenus() {
  Array.from(sidebar.getElementsByClassName('sub-menu')).forEach(ul => {
    ul.classList.remove('show');
    ul.previousElementSibling.classList.remove('rotate');
  });
}

function toggleSubMenu2(button) {
  // Cerrar solo los submenús de segundo nivel si se abre uno nuevo dentro del mismo primer nivel
  if (!button.nextElementSibling.classList.contains('show')) {
    closeAllSubMenus2();
  }

  button.nextElementSibling.classList.toggle('show');
  button.classList.toggle('rotate');
}

function closeAllSubMenus2() {
  Array.from(sidebar.getElementsByClassName('sub-menu2')).forEach(ul => {
    ul.classList.remove('show');
    ul.previousElementSibling.classList.remove('rotate');
  });
}

function cerrarSidebarEnMovilAlCargar() {
  const sidebar = document.getElementById('sidebar');
  if (sidebar && window.innerWidth <= 800) {
    sidebar.classList.add('close');
  } else if (sidebar && window.innerWidth > 800) {
    sidebar.classList.remove('close'); // Aseguramos que no esté cerrado en escritorio al cargar
  }
}

// Llama a la función cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', cerrarSidebarEnMovilAlCargar);

// Opcional: También puedes escuchar el evento resize para cambios de orientación
window.addEventListener('resize', cerrarSidebarEnMovilAlCargar);