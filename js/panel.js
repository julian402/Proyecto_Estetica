/**
 * Comportamiento común de los paneles con navegación lateral.
 *
 * Lo comparten el panel de administración (dashboard.php) y el área de
 * cliente (cuenta.php): ambos usan el mismo esqueleto de sidebar + vistas,
 * así que la lógica vive en un solo sitio.
 *
 * Expone window.PanelUI. Debe cargarse antes que dashboard.js o app.js.
 */
(function() {
  'use strict';

  // ============================================================
  // NAVEGACIÓN ENTRE VISTAS
  // Cada opción del menú muestra su sección dentro del panel,
  // en lugar de abrirse como ventana superpuesta.
  // ============================================================
  var topbarTitle    = document.querySelector('.topbar__title');
  var topbarSubtitle = document.querySelector('.topbar__subtitle');
  var tituloBase     = topbarTitle ? topbarTitle.textContent.trim() : '';
  var subtituloBase  = topbarSubtitle ? topbarSubtitle.textContent.trim() : '';

  // Vista que se muestra al entrar y a la que se vuelve al cancelar.
  var vistaInicial = document.querySelector('.view:not([hidden])');
  var idVistaInicial = vistaInicial ? vistaInicial.id : null;

  function showView(viewId) {
    var destino = document.getElementById(viewId);
    if (!destino) return;

    document.querySelectorAll('.view').forEach(function(vista) {
      vista.hidden = (vista !== destino);
    });

    // Marcar la opción correspondiente en el menú lateral
    document.querySelectorAll('.sidebar__item').forEach(function(item) {
      var activo = item.dataset.view === viewId;
      item.classList.toggle('sidebar__item--active', activo);
      if (activo) {
        item.setAttribute('aria-current', 'page');
      } else {
        item.removeAttribute('aria-current');
      }
    });

    // El título y el subtítulo de la barra superior acompañan a la vista
    var esInicial = (viewId === idVistaInicial);
    if (topbarTitle) {
      var nombre = destino.dataset.viewTitle;
      topbarTitle.textContent = (esInicial || !nombre) ? tituloBase : nombre;
    }
    if (topbarSubtitle) {
      var sub = destino.dataset.viewSubtitle;
      topbarSubtitle.textContent = (esInicial || !sub) ? subtituloBase : sub;
    }

    window.scrollTo({ top: 0, behavior: 'auto' });
  }

  // Un solo manejador para cualquier elemento que apunte a una vista
  // (opciones del menú y botones de volver dentro de las propias vistas)
  document.querySelectorAll('[data-view]').forEach(function(item) {
    item.addEventListener('click', function() {
      showView(item.dataset.view);
    });
  });

  // ============================================================
  // BARRA LATERAL (cajón en pantallas pequeñas)
  // ============================================================
  var sidebar       = document.getElementById('adminSidebar');
  var sidebarToggle = document.getElementById('sidebarToggle');
  var sidebarClose  = document.getElementById('sidebarClose');
  var adminOverlay  = document.getElementById('adminOverlay');

  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('sidebar--open');
    if (adminOverlay) adminOverlay.classList.remove('admin-overlay--visible');
    if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('sidebar-open');
  }

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('sidebar--open');
    if (adminOverlay) adminOverlay.classList.add('admin-overlay--visible');
    if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('sidebar-open');
  }

  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function() {
      if (sidebar.classList.contains('sidebar--open')) closeSidebar(); else openSidebar();
    });
  }
  if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
  if (adminOverlay) adminOverlay.addEventListener('click', closeSidebar);

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSidebar();
  });

  window.addEventListener('resize', function() {
    if (window.innerWidth > 1024) closeSidebar();
  });

  // Al elegir una opción del menú se cierra el panel en móvil
  if (sidebar) {
    sidebar.querySelectorAll('.sidebar__item').forEach(function(item) {
      item.addEventListener('click', function() {
        if (window.innerWidth <= 1024) closeSidebar();
      });
    });
  }

  // La vista inicial viene marcada desde el servidor. Su carga se dispara
  // en DOMContentLoaded: para entonces ya se ejecutaron los scripts que
  // traen los datos (app.js / dashboard.js), que es quien escucha el clic.
  document.addEventListener('DOMContentLoaded', function() {
    var inicial = document.querySelector('.sidebar__item--active[data-view]');
    if (inicial) inicial.click();
  });

  window.PanelUI = {
    showView: showView,
    openSidebar: openSidebar,
    closeSidebar: closeSidebar
  };
})();
