<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/User.php';

start_session();

// Area privada del cliente. El personal tiene su propio panel.
$currentUser = current_user();
if (!$currentUser) {
    redirect('index.php');
}
if (in_array((int) $currentUser['id_rol'], [2, 3, 4], true)) {
    redirect('dashboard.php');
}

$esteticistas = User::getEsteticistas();

// Vista inicial segun el enlace de entrada (?v=citas|perfil|favoritos)
$vistas = ['citas' => 'viewCitas', 'perfil' => 'viewPerfil', 'favoritos' => 'viewFavoritos'];
$vistaInicial = $vistas[$_GET['v'] ?? ''] ?? 'viewCitas';

$iniciales = mb_strtoupper(mb_substr($currentUser['nombre'], 0, 1));

// La barra superior arranca con el titulo de la vista de entrada
$encabezados = [
    'viewCitas'     => ['Mis citas', 'Consulta, reprograma o cancela tus reservas'],
    'viewPerfil'    => ['Mi perfil', 'Tus datos personales y preferencias de cuenta'],
    'viewFavoritos' => ['Tratamientos favoritos', 'Tus tratamientos guardados para agendar con un clic'],
];
[$tituloInicial, $subtituloInicial] = $encabezados[$vistaInicial];

/**
 * Iconos del area de cliente. Mismo criterio que el panel de administracion:
 * trazo que hereda currentColor.
 */
function cuenta_icon(string $name): string {
    $paths = [
        'calendar' => '<rect x="3" y="4.5" width="18" height="17" rx="2"/><line x1="3" y1="9.5" x2="21" y2="9.5"/><line x1="8" y1="2.5" x2="8" y2="6.5"/><line x1="16" y1="2.5" x2="16" y2="6.5"/>',
        'user'     => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'heart'    => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
        'home'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'menu'     => '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'close'    => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
    ];

    $d = $paths[$name] ?? '';
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" '
         . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Gestiona tus citas, tu perfil y tus tratamientos favoritos en Hanul Beauty.">
  <title>Mi cuenta - Hanul Beauty</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>">
</head>
<body class="admin-body">

  <div class="admin-overlay" id="adminOverlay"></div>

  <div class="admin-shell">

    <!-- ==================== BARRA LATERAL ==================== -->
    <aside class="sidebar" id="adminSidebar">
      <div class="sidebar__brand">
        <a href="index.php" class="sidebar__logo">
          <span class="logo__dot"></span>
          <span class="sidebar__logo-text">Hanul Beauty</span>
        </a>
        <button class="sidebar__close" id="sidebarClose" type="button" aria-label="Cerrar menú">
          <?php echo cuenta_icon('close'); ?>
        </button>
      </div>

      <nav class="sidebar__nav" aria-label="Secciones de mi cuenta">
        <p class="sidebar__section">Mi cuenta</p>

        <button class="sidebar__item<?php echo $vistaInicial === 'viewCitas' ? ' sidebar__item--active' : ''; ?>"
                id="openCitasBtn" type="button" data-view="viewCitas">
          <?php echo cuenta_icon('calendar'); ?>
          <span>Mis citas</span>
        </button>

        <button class="sidebar__item<?php echo $vistaInicial === 'viewPerfil' ? ' sidebar__item--active' : ''; ?>"
                id="openPerfilBtn" type="button" data-view="viewPerfil">
          <?php echo cuenta_icon('user'); ?>
          <span>Mi perfil</span>
        </button>

        <button class="sidebar__item<?php echo $vistaInicial === 'viewFavoritos' ? ' sidebar__item--active' : ''; ?>"
                id="openFavoritosBtn" type="button" data-view="viewFavoritos">
          <?php echo cuenta_icon('heart'); ?>
          <span>Favoritos</span>
        </button>

        <p class="sidebar__section">Sitio</p>
        <a class="sidebar__item" href="index.php">
          <?php echo cuenta_icon('home'); ?>
          <span>Volver al inicio</span>
        </a>
        <a class="sidebar__item" href="index.php#agendar">
          <?php echo cuenta_icon('calendar'); ?>
          <span>Agendar cita</span>
        </a>
      </nav>

      <div class="sidebar__footer">
        <div class="sidebar__user">
          <span class="sidebar__avatar" aria-hidden="true"><?php echo $iniciales; ?></span>
          <span class="sidebar__user-info">
            <span class="sidebar__user-name"><?php echo sanitize($currentUser['nombre']); ?></span>
            <span class="sidebar__user-mail"><?php echo sanitize($currentUser['correo']); ?></span>
          </span>
          <button class="sidebar__logout" id="logoutBtn" type="button" aria-label="Cerrar sesión" title="Cerrar sesión">
            <?php echo cuenta_icon('logout'); ?>
          </button>
        </div>
      </div>
    </aside>

    <!-- ==================== CONTENIDO ==================== -->
    <div class="admin-main">

      <header class="topbar">
        <button class="topbar__menu" id="sidebarToggle" type="button"
                aria-label="Abrir menú" aria-controls="adminSidebar" aria-expanded="false">
          <?php echo cuenta_icon('menu'); ?>
        </button>
        <div class="topbar__titles">
          <h1 class="topbar__title"><?php echo $tituloInicial; ?></h1>
          <p class="topbar__subtitle"><?php echo $subtituloInicial; ?></p>
        </div>
        <span class="topbar__date">Hola, <?php echo sanitize(explode(' ', trim($currentUser['nombre']))[0]); ?></span>
      </header>

      <main class="dashboard">

        <!-- ---------- Mis citas ---------- -->
        <section class="view" id="viewCitas"
                 data-view-title="Mis citas"
                 data-view-subtitle="Consulta, reprograma o cancela tus reservas"
                 <?php echo $vistaInicial === 'viewCitas' ? '' : 'hidden'; ?>>
          <div class="panel panel--view">
            <div class="modal__header-kbeauty">
              <h2 class="modal__title">Mis citas</h2>
              <p class="modal__subtitle">Consulta el estado de tus reservas, reprograma tus citas o gestiona tu agenda.</p>
            </div>
            <div id="citasList">
              <p class="modal__empty">Cargando tus citas...</p>
            </div>
          </div>
        </section>

        <!-- ---------- Mi perfil ---------- -->
        <section class="view" id="viewPerfil"
                 data-view-title="Mi perfil"
                 data-view-subtitle="Tus datos personales y preferencias de cuenta"
                 <?php echo $vistaInicial === 'viewPerfil' ? '' : 'hidden'; ?>>
          <div class="panel panel--view">
            <div class="modal__header-kbeauty">
              <div class="perfil-avatar" id="perfilAvatar">
                <span><?php echo $iniciales; ?></span>
              </div>
              <h2 class="modal__title">Mi perfil</h2>
              <p class="modal__subtitle">Gestiona tus datos personales y preferencias de cuenta.</p>
            </div>

            <form class="modal__form cuenta-form" id="perfilForm">
              <div class="form-group">
                <label class="form-label" for="perfilNombre">Nombre completo</label>
                <input type="text" class="form-input" id="perfilNombre" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="perfilCorreo">Correo electrónico</label>
                <input type="email" class="form-input" id="perfilCorreo" disabled title="El correo electrónico no puede ser modificado">
                <span class="modal__help">El correo es el identificador principal de tu cuenta.</span>
              </div>
              <div class="form-group">
                <label class="form-label" for="perfilTelefono">Teléfono de contacto</label>
                <input type="tel" class="form-input" id="perfilTelefono" placeholder="300 123 4567" pattern="[0-9+\s-]{7,15}">
              </div>

              <div class="user-menu__divider" style="margin: 20px 0 16px;"></div>
              <p class="modal__section-title">Cambiar contraseña (opcional)</p>

              <div class="form-group">
                <label class="form-label" for="perfilPassActual">Contraseña actual</label>
                <input type="password" class="form-input" id="perfilPassActual" placeholder="Solo si deseas cambiarla">
              </div>
              <div class="form-group">
                <label class="form-label" for="perfilPassNueva">Nueva contraseña</label>
                <input type="password" class="form-input" id="perfilPassNueva" minlength="8" maxlength="128" placeholder="Mínimo 8 caracteres">
              </div>

              <p class="modal__error" id="perfilError"></p>
              <button type="submit" class="btn btn--primary">Guardar cambios</button>
            </form>
          </div>
        </section>

        <!-- ---------- Favoritos ---------- -->
        <section class="view" id="viewFavoritos"
                 data-view-title="Tratamientos favoritos"
                 data-view-subtitle="Tus tratamientos guardados para agendar con un clic"
                 <?php echo $vistaInicial === 'viewFavoritos' ? '' : 'hidden'; ?>>
          <div class="panel panel--view">
            <div class="modal__header-kbeauty">
              <h2 class="modal__title">Tratamientos favoritos</h2>
              <p class="modal__subtitle">Tus tratamientos guardados para agendar con un solo clic.</p>
            </div>
            <div id="favoritosList">
              <p class="modal__empty">Cargando favoritos...</p>
            </div>
          </div>
        </section>

      </main>
    </div>
  </div>

  <!-- Reprogramar sigue siendo una ventana: se abre desde una cita concreta -->
  <?php require __DIR__ . '/templates/modal-reschedule.php'; ?>

  <!-- Toast container -->
  <div class="toast-container" id="toastContainer"></div>

  <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

  <script src="js/panel.js?v=<?php echo filemtime(__DIR__ . '/js/panel.js'); ?>"></script>
  <script src="js/app.js?v=<?php echo filemtime(__DIR__ . '/js/app.js'); ?>"></script>
</body>
</html>
