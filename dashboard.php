<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Appointment.php';
require_once __DIR__ . '/models/Treatment.php';

start_session();

// Solo usuarios logueados con rol admin (2), recepcion (3) o esteticista (4)
$currentUser = current_user();
if (!$currentUser || !in_array((int) $currentUser['id_rol'], [2, 3, 4])) {
    header('Location: index.php');
    exit;
}

$userRole      = (int) $currentUser['id_rol'];
$isSuperAdmin  = ($userRole === 2);
$isRecepcion   = ($userRole === 3);
$isEsteticista = ($userRole === 4);

$roleNames = [2 => 'SuperAdmin', 3 => 'Recepcionista', 4 => 'Esteticista'];
$roleLabel = $roleNames[$userRole] ?? 'Admin';

$esteticistas     = User::getEsteticistas();
$serviciosActivos = Treatment::getAll();
$subcategorias    = Treatment::getAllSubcategories();
$stats            = Appointment::countByStatus();
$totalCitas       = array_sum($stats);

/**
 * Iconos SVG del panel. Trazo heredado de currentColor, así que respetan el tema.
 */
function admin_icon(string $name): string {
    $paths = [
        'grid'   => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'phone'  => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
        'alert'  => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'clock'  => '<circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/>',
        'spark'  => '<path d="M12 2.5 13.9 8a4 4 0 0 0 2.6 2.6l5.5 1.9-5.5 1.9A4 4 0 0 0 13.9 17L12 22.5 10.1 17a4 4 0 0 0-2.6-2.6L2 12.5l5.5-1.9A4 4 0 0 0 10.1 8z"/>',
        'users'  => '<path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
        'chart'  => '<line x1="6" y1="20" x2="6" y2="13"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="18" y1="20" x2="18" y2="9"/>',
        'clip'   => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><line x1="8" y1="11" x2="16" y2="11"/><line x1="8" y1="15" x2="13" y2="15"/>',
        'mail'   => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'menu'   => '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'close'  => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'sun'    => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon'   => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
        'undo'   => '<polyline points="9 14 4 9 9 4"/><path d="M20 20v-5a6 6 0 0 0-6-6H4"/>',
        'redo'   => '<polyline points="15 14 20 9 15 4"/><path d="M4 20v-5a6 6 0 0 1 6-6h10"/>',
    ];

    $d = $paths[$name] ?? '';
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" '
         . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
}

/**
 * Fecha de hoy en español, para la barra superior.
 */
function strftime_es(): string {
    $dias  = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
              'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    return sprintf('%s, %d de %s', $dias[(int) date('w')], (int) date('j'), $meses[(int) date('n') - 1]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $isEsteticista ? 'Mi Agenda de Servicios' : 'Panel de Administración'; ?> - Hanul Beauty</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>">
  <script src="js/theme-init.js?v=<?php echo filemtime(__DIR__ . '/js/theme-init.js'); ?>"></script>
</head>
<body class="admin-body">
<div class="admin-overlay" id="adminOverlay"></div>

  <div class="admin-shell">
    <aside class="sidebar" id="adminSidebar">
      <div class="sidebar__brand">
        <a href="index.php" class="sidebar__logo">
          <span class="logo__dot"></span>
          <span class="sidebar__logo-text">Hanul Beauty</span>
        </a>
        <button class="sidebar__close" id="sidebarClose" type="button" aria-label="Cerrar menú">
          <?php echo admin_icon('close'); ?>
        </button>
      </div>

      <nav class="sidebar__nav" aria-label="Secciones del panel">
        <p class="sidebar__section">Principal</p>
        <button class="sidebar__item sidebar__item--active" id="navDashboard" type="button" data-view="viewDashboard">
          <?php echo admin_icon('grid'); ?>
          <span>Dashboard</span>
        </button>

        <p class="sidebar__section">Operación</p>
        <?php if ($isSuperAdmin || $isRecepcion): ?>
          <button class="sidebar__item" id="btnOpenNuevaCita" type="button" data-view="viewNuevaCita" title="Agendar una cita recibida por llamada telefónica">
            <?php echo admin_icon('phone'); ?>
            <span>Agendar cita</span>
          </button>
          <button class="sidebar__item" id="btnOpenContingencia" type="button" data-view="viewContingencia" title="Gestionar ausencias imprevistas y reasignar citas">
            <?php echo admin_icon('alert'); ?>
            <span>Contingencia</span>
          </button>
        <?php endif; ?>
<button class="sidebar__item" id="btnOpenBloqueo" type="button" data-view="viewBloqueo" title="Bloquear horarios de descanso, almuerzo o incapacidad">
          <?php echo admin_icon('clock'); ?>
          <span>Bloquear horario</span>
        </button>

        <?php if ($isSuperAdmin): ?>
          <p class="sidebar__section">Gestión</p>
          <button class="sidebar__item" id="btnOpenServicios" type="button" data-view="viewServicios" title="Administrar catálogo de tratamientos">
            <?php echo admin_icon('spark'); ?>
            <span>Tratamientos</span>
          </button>
          <button class="sidebar__item" id="btnOpenPersonal" type="button" data-view="viewPersonal" title="Administrar empleados y accesos">
            <?php echo admin_icon('users'); ?>
            <span>Personal</span>
          </button>
        <?php endif; ?>

        <?php if ($isSuperAdmin || $isRecepcion): ?>
          <p class="sidebar__section">Análisis</p>
          <button class="sidebar__item" id="btnOpenReporte" type="button" data-view="viewReporte" title="Generar informe imprimible con métricas">
            <?php echo admin_icon('chart'); ?>
            <span>Informe</span>
          </button>
          <?php if ($isSuperAdmin): ?>
            <button class="sidebar__item" id="btnOpenLogs" type="button" data-view="viewLogs" title="Ver registro histórico de actividades">
              <?php echo admin_icon('clip'); ?>
              <span>Auditoría</span>
            </button>
            <button class="sidebar__item" id="btnOpenCorreos" type="button" data-view="viewCorreos" title="Ver los correos enviados por el sistema">
              <?php echo admin_icon('mail'); ?>
              <span>Correos</span>
            </button>
          <?php endif; ?>
        <?php endif; ?>
      </nav>

      <div class="sidebar__footer">
        <button class="theme-switch" id="themeToggleBtn" type="button" aria-label="Alternar modo oscuro" title="Alternar modo oscuro">
          <span class="theme-switch__icon theme-switch__icon--sun"><?php echo admin_icon('sun'); ?></span>
          <span class="theme-switch__icon theme-switch__icon--moon"><?php echo admin_icon('moon'); ?></span>
          <span class="theme-switch__text" id="themeToggleText">Modo Oscuro</span>
        </button>

        <div class="sidebar__user">
          <span class="sidebar__avatar" aria-hidden="true"><?php echo mb_strtoupper(mb_substr($currentUser['nombre'], 0, 1)); ?></span>
          <span class="sidebar__user-info">
            <span class="sidebar__user-name"><?php echo sanitize($currentUser['nombre']); ?></span>
            <span class="user-pill__badge user-pill__badge--<?php echo strtolower($roleLabel); ?>"><?php echo $roleLabel; ?></span>
          </span>
          <button class="sidebar__logout" id="dashLogoutBtn" type="button" aria-label="Cerrar sesión" title="Cerrar sesión">
            <?php echo admin_icon('logout'); ?>
          </button>
        </div>
      </div>
    </aside>
    <div class="admin-main">

      <header class="topbar">
        <button class="topbar__menu" id="sidebarToggle" type="button" aria-label="Abrir menú" aria-controls="adminSidebar" aria-expanded="false">
          <?php echo admin_icon('menu'); ?>
        </button>
        <div class="topbar__titles">
          <?php if ($isEsteticista): ?>
            <h1 class="topbar__title">Mi Agenda de Servicios</h1>
            <p class="topbar__subtitle">Tus reservas asignadas y tus horarios de descanso</p>
          <?php elseif ($isRecepcion): ?>
            <h1 class="topbar__title">Panel de Recepción</h1>
            <p class="topbar__subtitle">Citas telefónicas, contingencias y reservas</p>
          <?php else: ?>
            <h1 class="topbar__title">Panel de Administración</h1>
            <p class="topbar__subtitle">Citas, servicios, personal y auditoría de Hanul Beauty</p>
          <?php endif; ?>
        </div>
        <span class="topbar__date"><?php echo ucfirst(strftime_es()); ?></span>
      </header>

      <main class="dashboard">
        <section class="view" id="viewDashboard" data-view-title="Dashboard">
<div class="dashboard__stats">
          <div class="dashboard__stat-card dashboard__stat-card--total">
            <span class="dashboard__stat-number" id="statTotal"><?php echo $totalCitas; ?></span>
            <span class="dashboard__stat-label">Total Citas</span>
          </div>
          <div class="dashboard__stat-card dashboard__stat-card--pendiente">
            <span class="dashboard__stat-number" id="statPendiente"><?php echo $stats['Pendiente'] ?? 0; ?></span>
            <span class="dashboard__stat-label">Pendientes</span>
          </div>
          <div class="dashboard__stat-card dashboard__stat-card--confirmada">
            <span class="dashboard__stat-number" id="statConfirmada"><?php echo $stats['Confirmada'] ?? 0; ?></span>
            <span class="dashboard__stat-label">Confirmadas</span>
          </div>
          <div class="dashboard__stat-card dashboard__stat-card--completada">
            <span class="dashboard__stat-number" id="statCompletada"><?php echo $stats['Completada'] ?? 0; ?></span>
            <span class="dashboard__stat-label">Completadas</span>
          </div>
        </div>
        <section class="panel">
          <div class="panel__head">
            <div>
              <h2 class="panel__title">Reservas</h2>
              <p class="panel__hint" id="reservasCount">Todas las citas registradas</p>
            </div>
            <div class="panel__tools">
              <button class="icon-btn" id="btnUndo" type="button" disabled title="Deshacer último cambio de estado" aria-label="Deshacer">
                <?php echo admin_icon('undo'); ?>
              </button>
              <button class="icon-btn" id="btnRedo" type="button" disabled title="Rehacer cambio de estado" aria-label="Rehacer">
                <?php echo admin_icon('redo'); ?>
              </button>
            </div>
          </div>

          <div class="filters">
            <div class="filters__field">
              <label class="filters__label" for="filterFecha">Fecha</label>
              <input type="date" class="form-input" id="filterFecha" title="Filtrar por fecha">
            </div>

            <div class="filters__field">
              <label class="filters__label" for="filterEstado">Estado</label>
              <select class="form-select" id="filterEstado">
                <option value="">Todos los estados</option>
                <option value="1">Pendiente</option>
                <option value="2">Confirmada</option>
                <option value="3">Completada</option>
                <option value="4">Cancelada</option>
                <option value="5">Reasignada</option>
                <option value="6">No Show</option>
              </select>
            </div>

            <div class="filters__field">
              <label class="filters__label" for="filterServicio">Servicio</label>
              <select class="form-select" id="filterServicio">
                <option value="">Todos los servicios</option>
                <?php foreach ($serviciosActivos as $s): ?>
                  <option value="<?php echo (int) $s['id_servicio']; ?>">
                    <?php echo sanitize($s['nombre_servicio']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="filters__field">
              <label class="filters__label" for="filterEsteticista">Especialista</label>
              <?php if ($isEsteticista): ?>
                <div class="filter-fixed-tag">
                  <span class="dot-online"></span>
                  <strong><?php echo sanitize($currentUser['nombre']); ?></strong> (Tú)
                </div>
                <input type="hidden" id="filterEsteticista" value="<?php echo (int) $currentUser['id_usuario']; ?>">
              <?php else: ?>
                <select class="form-select" id="filterEsteticista">
                  <option value="">Todos los especialistas</option>
                  <?php foreach ($esteticistas as $est): ?>
                    <option value="<?php echo (int) $est['id_usuario']; ?>">
                      <?php echo sanitize($est['nombre']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
            </div>

            <button class="btn btn--outline btn--sm filters__reset" id="btnResetFilters" type="button" title="Restablecer todos los filtros">
              Limpiar
            </button>
          </div>
          <div class="dashboard__table-wrap">
            <table class="dashboard__table" id="reservasTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Cliente</th>
                  <th>Servicio</th>
                  <th>Especialista</th>
                  <th>Fecha</th>
                  <th>Hora</th>
                  <th>Estado</th>
                  <th>Cambiar Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="reservasBody">
                <tr><td colspan="9" class="dashboard__empty">Cargando reservas...</td></tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="view" id="viewNuevaCita" data-view-subtitle="Registra una cita recibida por teléfono" data-view-title="Agendar cita" hidden aria-labelledby="modalNuevaTitle">
          <div class="panel panel--view">
            <h2 class="modal__title" id="modalNuevaTitle">📞 Agendar Cita Telefónica (Recepción)</h2>
            <p class="modal__note">Registra una cita directa para un cliente por recepción</p>
      
            <form class="modal__form" id="formNuevaCita">
              <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                  <label class="form-label" for="ncNombre">Nombre del Cliente *</label>
                  <input type="text" class="form-input" id="ncNombre" placeholder="Ej: Marcela Gómez" required>
                </div>
                <div class="form-group">
                  <label class="form-label" for="ncTelefono">Teléfono de Contacto</label>
                  <input type="tel" class="form-input" id="ncTelefono" placeholder="300 123 4567">
                </div>
              </div>

              <div class="form-group">
                <label class="form-label" for="ncCorreo">Correo Electrónico *</label>
                <input type="email" class="form-input" id="ncCorreo" placeholder="cliente@ejemplo.com" required>
              </div>

              <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                  <label class="form-label" for="ncServicio">Tratamiento *</label>
                  <select class="form-select" id="ncServicio" required>
                    <option value="">Selecciona servicio...</option>
                    <?php foreach ($serviciosActivos as $s): ?>
                      <option value="<?php echo (int) $s['id_servicio']; ?>" data-duracion="<?php echo (int) $s['duracion_minutos']; ?>" data-precio="<?php echo (float) $s['precio']; ?>">
                        <?php echo sanitize($s['nombre_servicio']); ?> (<?php echo (int) $s['duracion_minutos']; ?> min - $<?php echo number_format($s['precio'], 0, ',', '.'); ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label" for="ncEspecialista">Esteticista Asignado *</label>
                  <select class="form-select" id="ncEspecialista" required>
                    <option value="">Cualquier esteticista disponible</option>
                    <?php foreach ($esteticistas as $est): ?>
                      <option value="<?php echo (int) $est['id_usuario']; ?>"><?php echo sanitize($est['nombre']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                  <label class="form-label" for="ncFecha">Fecha *</label>
                  <input type="date" class="form-input" id="ncFecha" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                  <label class="form-label" for="ncHora">Horario Disponible *</label>
                  <select class="form-select" id="ncHora" required>
                    <option value="">Selecciona fecha y servicio primero</option>
                  </select>
                </div>
              </div>

              <p class="modal__error" id="ncError"></p>

              <div class="modal__btn-row">
                <button type="button" class="btn btn--outline btn--full" data-view="viewDashboard">Cancelar</button>
                <button type="submit" class="btn btn--primary btn--full" id="btnSubmitNuevaCita">Guardar y Confirmar Cita</button>
              </div>
            </form>
          </div>
        </section>

        <section class="view" id="viewBloqueo" data-view-subtitle="Bloquea descansos, almuerzos o incapacidades" data-view-title="Bloquear horario" hidden aria-labelledby="modalBloqTitle">
          <div class="panel panel--view">
            <h2 class="modal__title" id="modalBloqTitle">⏱ Bloqueo de Horarios y Descansos</h2>
            <p class="modal__note">
              Registra pausas de almuerzo, descansos o incapacidades para evitar solapamientos con reservas.
            </p>
            <form class="modal__form modal__panel" id="formBloqueo">
              <h4 style="font-size: 0.95rem; margin-bottom: 12px;">Nuevo Bloqueo</h4>
              <div class="form-row" style="display: grid; grid-template-columns: <?php echo $isEsteticista ? '1fr 1fr' : '1fr 1fr 1fr'; ?>; gap: 12px;">
                <?php if (!$isEsteticista): ?>
                <div class="form-group">
                  <label class="form-label" for="bloqEspecialista">Especialista *</label>
                  <select class="form-select" id="bloqEspecialista" required>
                    <?php foreach ($esteticistas as $est): ?>
                      <option value="<?php echo (int) $est['id_usuario']; ?>"><?php echo sanitize($est['nombre']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?php else: ?>
                  <input type="hidden" id="bloqEspecialista" value="<?php echo (int) $currentUser['id_usuario']; ?>">
                <?php endif; ?>

                <div class="form-group">
                  <label class="form-label" for="bloqFecha">Fecha *</label>
                  <input type="date" class="form-input" id="bloqFecha" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                  <label class="form-label" for="bloqMotivo">Motivo *</label>
                  <select class="form-select" id="bloqMotivo">
                    <option value="Almuerzo">Almuerzo</option>
                    <option value="Break / Pausa activa">Break / Pausa activa</option>
                    <option value="Incapacidad médica">Incapacidad médica</option>
                    <option value="Capacitación interna">Capacitación interna</option>
                    <option value="Asunto personal">Asunto personal</option>
                  </select>
                </div>
              </div>

              <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                <div class="form-group">
                  <label class="form-label" for="bloqHoraInicio">Hora Inicio *</label>
                  <input type="time" class="form-input" id="bloqHoraInicio" required value="13:00">
                </div>
                <div class="form-group">
                  <label class="form-label" for="bloqHoraFin">Hora Fin *</label>
                  <input type="time" class="form-input" id="bloqHoraFin" required value="14:00">
                </div>
              </div>

              <p class="modal__error" id="bloqError"></p>

              <button type="submit" class="btn btn--primary btn--sm" style="margin-top: 12px;" id="btnSubmitBloqueo">
                + Guardar Bloqueo
              </button>
            </form>
            <h4 style="font-size: 0.95rem; margin-bottom: 8px;">Bloqueos Registrados</h4>
            <div class="modal__scroll">
              <table class="dashboard__table" style="font-size: 0.82rem;">
                <thead>
                  <tr>
                    <th>Especialista</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Motivo</th>
                    <th>Acción</th>
                  </tr>
                </thead>
                <tbody id="tablaBloqueosBody">
                  <tr><td colspan="5" class="dashboard__empty">Cargando bloqueos...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section class="view" id="viewContingencia" data-view-subtitle="Reasigna o cancela citas por ausencia imprevista" data-view-title="Contingencia" hidden aria-labelledby="modalContTitle">
          <div class="panel panel--view">
            <h2 class="modal__title" id="modalContTitle">🚨 Contingencia de Especialista</h2>
            <p class="modal__note">
              Gestiona la ausencia imprevista de un especialista reasignando sus citas afectadas o notificando cancelaciones.
            </p>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: flex-end; margin-bottom: 20px;">
              <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="contEsteticista">Esteticista Ausente *</label>
                <select class="form-select" id="contEsteticista">
                  <?php foreach ($esteticistas as $est): ?>
                    <option value="<?php echo (int) $est['id_usuario']; ?>"><?php echo sanitize($est['nombre']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="contFecha">Fecha de la Ausencia *</label>
                <input type="date" class="form-input" id="contFecha" value="<?php echo date('Y-m-d'); ?>">
              </div>
              <button type="button" class="btn btn--primary btn--sm" id="btnConsultarContingencia" style="height: 40px;">
                Buscar Citas
              </button>
            </div>

            <div id="contResultados" style="display: none;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h4 style="font-size: 0.92rem;">Citas Afectadas (<span id="contTotalAfectadas">0</span>)</h4>
                <button type="button" class="btn btn--outline btn--sm" id="btnBloquearDiaContingencia" style="font-size: 0.75rem;">
                  ⛔ Bloquear Día Completo
                </button>
              </div>

              <div class="modal__scroll" style="margin-bottom: 16px;">
                <table class="dashboard__table" style="font-size: 0.82rem;">
                  <thead>
                    <tr>
                      <th width="30"><input type="checkbox" id="contSelectAll"></th>
                      <th>Cita #</th>
                      <th>Horario</th>
                      <th>Cliente</th>
                      <th>Tratamiento</th>
                      <th>Estado</th>
                    </tr>
                  </thead>
                  <tbody id="contCitasBody">
                  </tbody>
                </table>
              </div>
              <div class="cont-actions modal__panel">
                <h5 style="font-size: 0.85rem; margin-bottom: 10px;">Acciones sobre citas seleccionadas:</h5>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                  <select class="form-select" id="contNuevoEsteticista" style="flex: 1; min-width: 180px;">
                    <option value="">Reasignar a especialista...</option>
                    <?php foreach ($esteticistas as $est): ?>
                      <option value="<?php echo (int) $est['id_usuario']; ?>"><?php echo sanitize($est['nombre']); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="button" class="btn btn--primary btn--sm" id="btnReasignarLote">
                    Reasignar Citas
                  </button>
                  <button type="button" class="btn btn--outline btn--sm text-danger" id="btnCancelarLote">
                    Cancelar Citas
                  </button>
                </div>
              </div>
            </div>
          </div>
<div class="cont-historial">
            <div class="cont-historial__head">
              <h4 class="modal__section-title">Acciones realizadas</h4>
              <button type="button" class="btn btn--outline btn--xs" id="btnRefrescarHistorial">Actualizar</button>
            </div>
            <ul class="timeline" id="contHistorial">
              <li class="timeline__vacio">Cargando…</li>
            </ul>
          </div>
        </section>

        <section class="view" id="viewServicios" data-view-subtitle="Catálogo de tratamientos del estudio" data-view-title="Tratamientos" hidden aria-labelledby="modalServTitle">
          <div class="panel panel--view">
            <h2 class="modal__title" id="modalServTitle">💆‍♀️ Gestión de Tratamientos</h2>
            <form class="modal__form modal__panel" id="formServicioCrud">
              <input type="hidden" id="crudServicioId">
              <h4 id="crudServicioTitle" style="font-size: 0.95rem; margin-bottom: 10px;">Nuevo Tratamiento</h4>
              <div class="form-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px;">
                <div class="form-group">
                  <label class="form-label" for="crudServNombre">Nombre *</label>
                  <input type="text" class="form-input" id="crudServNombre" required placeholder="Ej: Glass Skin Facial">
                </div>
                <div class="form-group">
                  <label class="form-label" for="crudServPrecio">Precio (COP) *</label>
                  <input type="number" class="form-input" id="crudServPrecio" required min="0" step="1000" placeholder="180000">
                </div>
                <div class="form-group">
                  <label class="form-label" for="crudServDuracion">Duración (min) *</label>
                  <input type="number" class="form-input" id="crudServDuracion" required min="15" step="15" value="60">
                </div>
              </div>

              <div class="form-row" style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px; margin-top: 10px;">
                <div class="form-group">
                  <label class="form-label" for="crudServSubcat">Subcategoría *</label>
                  <select class="form-select" id="crudServSubcat">
                    <?php foreach ($subcategorias as $sc): ?>
                      <option value="<?php echo (int) $sc['id_subcategoria']; ?>">
                        <?php echo sanitize($sc['nombre_subcategoria']); ?> (<?php echo sanitize($sc['nombre_categoria']); ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label" for="crudServDesc">Descripción</label>
                  <input type="text" class="form-input" id="crudServDesc" placeholder="Breve descripción del procedimiento">
                </div>
              </div>
              <div class="form-group" style="margin-top: 10px;">
                <label class="form-label" for="crudServImagen">Imagen del tratamiento (opcional)</label>
                <input type="file" class="form-input" id="crudServImagen" accept="image/jpeg,image/png,image/webp">
                <p class="form-hint">JPG, PNG o WebP de máximo 5 MB. Si no cargas una imagen se conserva la actual o la predeterminada.</p>
              </div>

              <div style="display: flex; gap: 10px; margin-top: 12px;">
                <button type="submit" class="btn btn--primary btn--sm" id="btnSaveServicio">Guardar Tratamiento</button>
                <button type="button" class="btn btn--outline btn--sm" id="btnCancelServicioEdit" style="display: none;">Cancelar Edición</button>
              </div>
            </form>
            <h4 style="font-size: 0.95rem; margin-bottom: 8px;">Catálogo Actual</h4>
            <div class="modal__scroll">
              <table class="dashboard__table" style="font-size: 0.82rem;">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Duración</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="tablaServiciosBody">
                  <tr><td colspan="6" class="dashboard__empty">Cargando catálogo...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section class="view" id="viewPersonal" data-view-subtitle="Empleados, roles y accesos" data-view-title="Personal" hidden aria-labelledby="modalPersTitle">
          <div class="panel panel--view">
            <h2 class="modal__title" id="modalPersTitle">👥 Gestión de Personal</h2>
            <form class="modal__form modal__panel" id="formPersonalCrud">
              <input type="hidden" id="crudStaffId">
              <h4 id="crudStaffTitle" style="font-size: 0.95rem; margin-bottom: 10px;">Nuevo Empleado</h4>
        
              <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                <div class="form-group">
                  <label class="form-label" for="crudStaffNombre">Nombre Completo *</label>
                  <input type="text" class="form-input" id="crudStaffNombre" required placeholder="Nombre">
                </div>
                <div class="form-group">
                  <label class="form-label" for="crudStaffCorreo">Correo Electrónico *</label>
                  <input type="email" class="form-input" id="crudStaffCorreo" required placeholder="correo@hanulbeauty.co">
                </div>
                <div class="form-group">
                  <label class="form-label" for="crudStaffRol">Rol Asignado *</label>
                  <select class="form-select" id="crudStaffRol">
                    <option value="4">Esteticista</option>
                    <option value="3">Recepcionista</option>
                    <option value="2">SuperAdmin</option>
                  </select>
                </div>
              </div>

              <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                <div class="form-group">
                  <label class="form-label" for="crudStaffTelefono">Teléfono</label>
                  <input type="tel" class="form-input" id="crudStaffTelefono" placeholder="300 000 0000">
                </div>
                <div class="form-group">
                  <label class="form-label" for="crudStaffPassword">Contraseña <small id="passHelpText">(requerida al crear)</small></label>
                  <input type="password" class="form-input" id="crudStaffPassword" placeholder="Mínimo 8 caracteres">
                </div>
              </div>

              <div style="display: flex; gap: 10px; margin-top: 12px;">
                <button type="submit" class="btn btn--primary btn--sm" id="btnSaveStaff">Guardar Empleado</button>
                <button type="button" class="btn btn--outline btn--sm" id="btnCancelStaffEdit" style="display: none;">Cancelar Edición</button>
              </div>
            </form>
            <h4 style="font-size: 0.95rem; margin-bottom: 8px;">Equipo Registrado</h4>
            <div class="modal__scroll">
              <table class="dashboard__table" style="font-size: 0.82rem;">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="tablaPersonalBody">
                  <tr><td colspan="6" class="dashboard__empty">Cargando personal...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section class="view" id="viewLogs" data-view-subtitle="Registro histórico de actividades" data-view-title="Auditoría" hidden aria-labelledby="modalLogsTitle">
          <div class="panel panel--view">
            <h2 class="modal__title" id="modalLogsTitle">📋 Logs de Auditoría del Sistema</h2>
            <p class="modal__note">
              Registro inmutable de acciones realizadas por administradores, recepcionistas y especialistas.
            </p>

            <div class="modal__scroll">
              <table class="dashboard__table" style="font-size: 0.82rem;">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Fecha y Hora</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Acción Realizada</th>
                    <th>Tabla</th>
                  </tr>
                </thead>
                <tbody id="tablaLogsBody">
                  <tr><td colspan="6" class="dashboard__empty">Cargando logs...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section class="view" id="viewCorreos" data-view-subtitle="Notificaciones enviadas por el sistema" data-view-title="Correos" hidden aria-labelledby="modalCorreosTitle">
          <div class="panel panel--view">
            <div class="modal__header-kbeauty">
              <h2 class="modal__title" id="modalCorreosTitle">Correos Enviados</h2>
              <p class="modal__subtitle">
                Historial de notificaciones transaccionales.
                <span id="correosTransporte" class="modal__badge"></span>
              </p>
            </div>

            <div class="modal__scroll">
              <table class="dashboard__table dashboard__table--compact">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Fecha y Hora</th>
                    <th>Destinatario</th>
                    <th>Asunto</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody id="tablaCorreosBody">
                  <tr><td colspan="6" class="dashboard__empty">Cargando correos...</td></tr>
                </tbody>
              </table>
            </div>

            <div id="correoPreview" class="correo-preview" style="display:none;">
              <div class="correo-preview__head">
                <strong id="correoPreviewAsunto"></strong>
                <button type="button" class="btn btn--outline btn--xs" id="btnCerrarPreview">Cerrar vista previa</button>
              </div>
              <iframe id="correoPreviewFrame" title="Vista previa del correo"></iframe>
            </div>
          </div>
        </section>

        <section class="view" id="viewReporte" data-view-subtitle="Métricas e informe imprimible" data-view-title="Informe" hidden aria-labelledby="modalRepTitle">
          <div class="panel panel--view">
            <div class="printable-report" id="printableReportArea">
              <div class="report-header">
                <div class="report-header__brand">
                  <span class="report-logo-dot"></span>
                  <h2>Hanul Beauty</h2>
                  <p>K-Beauty Care & Wellness Studio</p>
                </div>
                <div class="report-header__meta">
                  <h3 style="font-family: var(--font-heading); font-size: 1.4rem;">Informe Ejecutivo de Reservas</h3>
                  <p><strong>Generado:</strong> <span id="repFechaGen"><?php echo date('d/m/Y H:i'); ?></span></p>
                  <p><strong>Período:</strong> <span id="repPeriodoLabel">Histórico Completo</span></p>
                  <p><strong>Emitido por:</strong> <?php echo sanitize($currentUser['nombre']); ?> (<?php echo $roleLabel; ?>)</p>
                </div>
              </div>
              <div class="report-filters no-print" style="margin: 16px 0; padding: 12px; background: var(--color-bg-section); border-radius: 8px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 6px;">
                  <label class="filter-label" for="repDesde">Desde:</label>
                  <input type="date" class="form-input" id="repDesde" style="width: 140px; padding: 6px 10px;">
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                  <label class="filter-label" for="repHasta">Hasta:</label>
                  <input type="date" class="form-input" id="repHasta" style="width: 140px; padding: 6px 10px;" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <button type="button" class="btn btn--primary btn--sm" id="btnRecalcularReporte">
                  Recalcular Métricas
                </button>
                <button type="button" class="btn btn--secondary btn--sm" id="btnImprimirReporte" style="margin-left: auto;">
                  🖨 Imprimir Informe (PDF)
                </button>
              </div>
              <div class="report-kpis" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;">
                <div class="report-kpi-card">
                  <span class="report-kpi-val" id="kpiTotalCitas">0</span>
                  <span class="report-kpi-label">Total Citas</span>
                </div>
                <div class="report-kpi-card">
                  <span class="report-kpi-val" id="kpiIngresosReales">$0</span>
                  <span class="report-kpi-label">Ingresos Reales (Completadas)</span>
                </div>
                <div class="report-kpi-card">
                  <span class="report-kpi-val" id="kpiIngresosProyectados">$0</span>
                  <span class="report-kpi-label">Ingresos Proyectados</span>
                </div>
                <div class="report-kpi-card">
                  <span class="report-kpi-val" id="kpiTasaEfectividad">0%</span>
                  <span class="report-kpi-label">Efectividad de Asistencia</span>
                </div>
              </div>
              <div class="report-breakdowns" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div class="report-box">
                  <h4 style="margin-bottom: 8px; font-size: 0.9rem;">Citas por Estado</h4>
                  <div id="repEstadosContainer">
                  </div>
                </div>
                <div class="report-box">
                  <h4 style="margin-bottom: 8px; font-size: 0.9rem;">Rendimiento por Especialista</h4>
                  <div id="repEspecialistasContainer">
                  </div>
                </div>
              </div>
              <div class="report-box" style="margin-bottom: 20px;">
                <h4 style="margin-bottom: 8px; font-size: 0.9rem;">Tratamientos Más Solicitados</h4>
                <div id="repServiciosContainer">
                </div>
              </div>
              <div class="report-table-box">
                <h4 style="margin-bottom: 8px; font-size: 0.9rem;">Detalle de Reservas del Período</h4>
                <div class="modal__scroll">
                  <table class="dashboard__table" style="font-size: 0.8rem;" id="tablaReporteCitas">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Fecha/Hora</th>
                        <th>Cliente</th>
                        <th>Tratamiento</th>
                        <th>Especialista</th>
                        <th>Precio</th>
                        <th>Estado</th>
                      </tr>
                    </thead>
                    <tbody id="tablaReporteCitasBody">
                      <tr><td colspan="7" class="dashboard__empty">Generando informe...</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="report-footer print-only" style="display: none; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 12px; font-size: 0.75rem; color: #666; justify-content: space-between;">
                <span>Hanul Beauty - Sistema K-Beauty</span>
                <span>Confidencial - Uso Administrativo</span>
              </div>

            </div>
          </div>
        </section>

      </main>
    </div>
  </div>
<div class="modal" id="modalReprogramar" role="dialog" aria-modal="true" aria-labelledby="modalReprogTitle">
    <div class="modal__content">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <h2 class="modal__title" id="modalReprogTitle">Reprogramar Cita</h2>
      <form class="modal__form" id="formReprogramar">
        <input type="hidden" id="reprogReservaId" name="reserva_id">
        <input type="hidden" id="reprogServicioId" name="servicio_id">
        
        <div class="reprog-summary modal__summary-box" id="reprogSummaryBox">
</div>

        <div class="form-group">
          <label class="form-label" for="reprogEspecialista">Especialista</label>
          <select class="form-select" id="reprogEspecialista" name="id_esteticista" <?php echo $isEsteticista ? 'disabled' : ''; ?> required>
            <?php foreach ($esteticistas as $est): ?>
              <option value="<?php echo (int) $est['id_usuario']; ?>"><?php echo sanitize($est['nombre']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="reprogFecha">Nueva Fecha</label>
          <input type="date" class="form-input" id="reprogFecha" name="nueva_fecha" required min="<?php echo date('Y-m-d'); ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="reprogHora">Nueva Hora (Horarios Disponibles)</label>
          <select class="form-select" id="reprogHora" name="nueva_hora" required>
            <option value="">Selecciona una fecha primero</option>
          </select>
          <small class="form-help modal__help" id="reprogSlotsHelp">
            Consultando disponibilidad en tiempo real...
          </small>
        </div>

        <p class="modal__error" id="reprogError"></p>
        
        <div class="modal__btn-row">
          <button type="button" class="btn btn--outline btn--full" data-close-modal>Cancelar</button>
          <button type="submit" class="btn btn--primary btn--full" id="btnSubmitReprog">Confirmar Reprogramación</button>
        </div>
      </form>
    </div>
  </div>

<div class="toast-container" id="toastContainer"></div>
<input type="hidden" id="dashCsrf" value="<?php echo csrf_token(); ?>">
  <input type="hidden" id="currentUserRole" value="<?php echo $userRole; ?>">
  <input type="hidden" id="currentUserId" value="<?php echo (int)$currentUser['id_usuario']; ?>">

  <script src="js/panel.js?v=<?php echo filemtime(__DIR__ . '/js/panel.js'); ?>"></script>
  <script src="js/dashboard.js?v=<?php echo filemtime(__DIR__ . '/js/dashboard.js'); ?>"></script>
</body>
</html>
