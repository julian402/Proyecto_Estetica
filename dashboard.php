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
$stats            = Appointment::countByStatus();
$totalCitas       = array_sum($stats);
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
  <script>
    // Aplicar dark mode antes de renderizar para prevenir parpadeo (FOUC)
    if (localStorage.getItem('admin_theme') === 'dark') {
      document.documentElement.classList.add('dark-mode');
    }
  </script>
</head>
<body>

  <!-- ==================== HEADER ADMIN ==================== -->
  <header class="header" id="header">
    <div class="container header__inner">
      <a href="index.php" class="logo">
        <span class="logo__dot"></span>
        <span class="logo__text">Hanul Beauty</span>
      </a>

      <div class="header__actions">
        <!-- Tarea 16: Switch Dark Mode -->
        <button class="theme-toggle" id="themeToggleBtn" type="button" aria-label="Alternar Modo Oscuro" title="Alternar Modo Oscuro">
          <span class="theme-toggle__icon theme-toggle__icon--sun" aria-hidden="true">☀️</span>
          <span class="theme-toggle__track"><span class="theme-toggle__thumb"></span></span>
          <span class="theme-toggle__icon theme-toggle__icon--moon" aria-hidden="true">🌙</span>
          <span class="theme-toggle__text" id="themeToggleText">Modo Oscuro</span>
        </button>

        <div class="user-pill">
          <span class="user-pill__badge user-pill__badge--<?php echo strtolower($roleLabel); ?>">
            <?php echo $roleLabel; ?>
          </span>
          <span class="user-pill__name"><?php echo sanitize($currentUser['nombre']); ?></span>
        </div>

        <button class="btn btn--primary btn--sm" id="dashLogoutBtn">Cerrar sesión</button>
      </div>
    </div>
  </header>

  <!-- ==================== DASHBOARD ==================== -->
  <section class="dashboard">
    <div class="container">

      <!-- Encabezado del Dashboard según Rol (Tarea 8) -->
      <div class="dashboard__header">
        <div>
          <?php if ($isEsteticista): ?>
            <h1 class="section__title" style="margin-bottom: 4px;">Mi Agenda de Servicios</h1>
            <p style="color: #888; font-size: 0.88rem;">Visualiza y gestiona tus reservas asignadas y tus horarios de descanso</p>
          <?php elseif ($isRecepcion): ?>
            <h1 class="section__title" style="margin-bottom: 4px;">Panel de Recepción</h1>
            <p style="color: #888; font-size: 0.88rem;">Gestión central de citas telefónicas, contingencias y reservas</p>
          <?php else: ?>
            <h1 class="section__title" style="margin-bottom: 4px;">Panel de Administración</h1>
            <p style="color: #888; font-size: 0.88rem;">Control maestro de citas, servicios, personal y auditoría de Hanul Beauty</p>
          <?php endif; ?>
        </div>

        <!-- Barra de Acciones Globales según Rol -->
        <div class="dashboard__actions-bar">
          <?php if ($isSuperAdmin || $isRecepcion): ?>
            <button class="btn btn--primary btn--sm" id="btnOpenNuevaCita" title="Agendar una cita recibida por llamada telefónica">
              <span class="btn__icon">📞</span> Agendar Cita (Recepción)
            </button>
            <button class="btn btn--secondary btn--sm" id="btnOpenContingencia" title="Gestionar ausencias imprevistas y reasignar citas">
              <span class="btn__icon">🚨</span> Contingencia (HU14)
            </button>
          <?php endif; ?>

          <!-- Bloqueo de agenda disponible para esteticista (HU13) y administradores -->
          <button class="btn btn--secondary btn--sm" id="btnOpenBloqueo" title="Bloquear horarios de descanso, almuerzo o incapacidad">
            <span class="btn__icon">⏱</span> Bloquear Horario (HU13)
          </button>

          <?php if ($isSuperAdmin): ?>
            <button class="btn btn--outline btn--sm" id="btnOpenServicios" title="Administrar catálogo de tratamientos">
              <span class="btn__icon">💆‍♀️</span> Servicios
            </button>
            <button class="btn btn--outline btn--sm" id="btnOpenPersonal" title="Administrar empleados y accesos">
              <span class="btn__icon">👥</span> Personal
            </button>
            <button class="btn btn--outline btn--sm" id="btnOpenLogs" title="Ver registro histórico de actividades">
              <span class="btn__icon">📋</span> Auditoría
            </button>
          <?php endif; ?>

          <?php if ($isSuperAdmin || $isRecepcion): ?>
            <button class="btn btn--outline btn--sm" id="btnOpenReporte" title="Generar informe imprimible con métricas">
              <span class="btn__icon">📊</span> Generar Informe
            </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="dashboard__stats">
        <div class="dashboard__stat-card">
          <span class="dashboard__stat-number" id="statTotal"><?php echo $totalCitas; ?></span>
          <span class="dashboard__stat-label">Total Citas</span>
        </div>
        <div class="dashboard__stat-card">
          <span class="dashboard__stat-number" id="statPendiente"><?php echo $stats['Pendiente'] ?? 0; ?></span>
          <span class="dashboard__stat-label">Pendientes</span>
        </div>
        <div class="dashboard__stat-card">
          <span class="dashboard__stat-number" id="statConfirmada"><?php echo $stats['Confirmada'] ?? 0; ?></span>
          <span class="dashboard__stat-label">Confirmadas</span>
        </div>
        <div class="dashboard__stat-card">
          <span class="dashboard__stat-number" id="statCompletada"><?php echo $stats['Completada'] ?? 0; ?></span>
          <span class="dashboard__stat-label">Completadas</span>
        </div>
      </div>

      <!-- Barra de Filtros y Acciones de Tabla (Tarea 18, Tarea 8, Tarea 3) -->
      <div class="dashboard__filters-wrap">
        <div class="dashboard__filters">
          <!-- Filtro Fecha -->
          <div class="filter-group">
            <label class="filter-label" for="filterFecha">Fecha:</label>
            <input type="date" class="form-input" id="filterFecha" title="Filtrar por fecha">
          </div>

          <!-- Filtro Estado -->
          <div class="filter-group">
            <label class="filter-label" for="filterEstado">Estado:</label>
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

          <!-- Tarea 18: Filtro por Servicio -->
          <div class="filter-group">
            <label class="filter-label" for="filterServicio">Servicio:</label>
            <select class="form-select" id="filterServicio">
              <option value="">Todos los servicios</option>
              <?php foreach ($serviciosActivos as $s): ?>
                <option value="<?php echo (int) $s['id_servicio']; ?>">
                  <?php echo sanitize($s['nombre_servicio']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Tarea 8: Filtro por Esteticista (Oculto o bloqueado para Esteticista) -->
          <div class="filter-group">
            <label class="filter-label">Especialista:</label>
            <?php if ($isEsteticista): ?>
              <div class="filter-fixed-tag">
                <span class="dot-online"></span>
                <strong><?php echo sanitize($currentUser['nombre']); ?></strong> (Tú)
              </div>
              <input type="hidden" id="filterEsteticista" value="<?php echo (int) $currentUser['id_usuario']; ?>">
            <?php else: ?>
              <select class="form-select" id="filterEsteticista">
                <option value="">Todos los esteticistas</option>
                <?php foreach ($esteticistas as $est): ?>
                  <option value="<?php echo (int) $est['id_usuario']; ?>">
                    <?php echo sanitize($est['nombre']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>

          <button class="btn btn--outline btn--sm" id="btnResetFilters" title="Restablecer todos los filtros">
            ↺ Limpiar
          </button>
        </div>

        <!-- Tarea 3: Deshacer / Rehacer -->
        <div class="dashboard__undo-redo">
          <button class="btn btn--outline btn--sm" id="btnUndo" disabled title="Deshacer último cambio de estado">
            ↩ Deshacer
          </button>
          <button class="btn btn--outline btn--sm" id="btnRedo" disabled title="Rehacer cambio de estado">
            ↪ Rehacer
          </button>
        </div>
      </div>

      <!-- Tabla de reservas (Tarea 23: Reprogramar) -->
      <div class="dashboard__table-wrap">
        <table class="dashboard__table" id="reservasTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Cliente</th>
              <th>Servicio</th>
              <th>Esteticista</th>
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

    </div>
  </section>

  <!-- ==================== MODALES ADMINISTRATIVOS ==================== -->

  <!-- 1. Modal Reprogramar Cita (Admin / Esteticista) - Tarea 23 -->
  <div class="modal" id="modalReprogramar" role="dialog" aria-modal="true" aria-labelledby="modalReprogTitle">
    <div class="modal__content">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <h2 class="modal__title" id="modalReprogTitle">Reprogramar Cita</h2>
      <form class="modal__form" id="formReprogramar">
        <input type="hidden" id="reprogReservaId" name="reserva_id">
        <input type="hidden" id="reprogServicioId" name="servicio_id">
        
        <div class="reprog-summary" id="reprogSummaryBox" style="margin-bottom: 16px; padding: 12px; background: rgba(138,154,106,0.1); border-radius: 8px;">
          <!-- Llenado dinámicamente con JS -->
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
          <small class="form-help" id="reprogSlotsHelp" style="color: #888; font-size: 0.78rem; display:block; margin-top:4px;">
            Consultando disponibilidad en tiempo real...
          </small>
        </div>

        <p class="modal__error" id="reprogError" style="color: #e74c3c; font-size: 0.85rem; display: none; margin-bottom: 12px;"></p>
        
        <div class="modal__btn-row" style="display: flex; gap: 10px; margin-top: 20px;">
          <button type="button" class="btn btn--outline btn--full" data-close-modal>Cancelar</button>
          <button type="submit" class="btn btn--primary btn--full" id="btnSubmitReprog">Confirmar Reprogramación</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 2. Modal Nueva Cita por Teléfono (Recepción - HU11) -->
  <div class="modal" id="modalNuevaCita" role="dialog" aria-modal="true" aria-labelledby="modalNuevaTitle">
    <div class="modal__content modal__content--wide">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <h2 class="modal__title" id="modalNuevaTitle">📞 Agendar Cita Telefónica (Recepción)</h2>
      <p style="font-size: 0.85rem; color: #888; margin-bottom: 20px;">Registra una cita directa para un cliente por recepción</p>
      
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

        <p class="modal__error" id="ncError" style="color: #e74c3c; font-size: 0.85rem; display: none; margin-bottom: 12px;"></p>

        <div class="modal__btn-row" style="display: flex; gap: 10px; margin-top: 24px;">
          <button type="button" class="btn btn--outline btn--full" data-close-modal>Cancelar</button>
          <button type="submit" class="btn btn--primary btn--full" id="btnSubmitNuevaCita">Guardar y Confirmar Cita</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 3. Modal Bloqueo de Agenda / Breaks (HU13) -->
  <div class="modal" id="modalBloqueo" role="dialog" aria-modal="true" aria-labelledby="modalBloqTitle">
    <div class="modal__content modal__content--wide">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <h2 class="modal__title" id="modalBloqTitle">⏱ Bloqueo de Horarios y Breaks (HU13)</h2>
      <p style="font-size: 0.85rem; color: #888; margin-bottom: 16px;">
        Registra pausas de almuerzo, descansos o incapacidades para evitar solapamientos con reservas.
      </p>

      <!-- Formulario de nuevo bloqueo -->
      <form class="modal__form" id="formBloqueo" style="background: rgba(0,0,0,0.03); padding: 16px; border-radius: 8px; margin-bottom: 20px;">
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

        <p class="modal__error" id="bloqError" style="color: #e74c3c; font-size: 0.85rem; display: none; margin-top: 8px;"></p>

        <button type="submit" class="btn btn--primary btn--sm" style="margin-top: 12px;" id="btnSubmitBloqueo">
          + Guardar Bloqueo
        </button>
      </form>

      <!-- Tabla de bloqueos activos -->
      <h4 style="font-size: 0.95rem; margin-bottom: 8px;">Bloqueos Registrados</h4>
      <div class="modal__scroll" style="max-height: 220px;">
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
  </div>

  <!-- 4. Modal Contingencia de Esteticista (HU14) -->
  <div class="modal" id="modalContingencia" role="dialog" aria-modal="true" aria-labelledby="modalContTitle">
    <div class="modal__content modal__content--wide">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <h2 class="modal__title" id="modalContTitle">🚨 Contingencia de Esteticista (HU14)</h2>
      <p style="font-size: 0.85rem; color: #888; margin-bottom: 16px;">
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

        <div class="modal__scroll" style="max-height: 240px; margin-bottom: 16px;">
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
              <!-- Renderizado con JS -->
            </tbody>
          </table>
        </div>

        <!-- Acciones en lote -->
        <div class="cont-actions" style="background: rgba(0,0,0,0.03); padding: 14px; border-radius: 8px;">
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
            <button type="button" class="btn btn--outline btn--sm" id="btnCancelarLote" style="color: #e74c3c;">
              Cancelar Citas
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 5. Modal Gestión de Servicios (CRUD SuperAdmin) -->
  <div class="modal" id="modalServicios" role="dialog" aria-modal="true" aria-labelledby="modalServTitle">
    <div class="modal__content modal__content--wide">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <h2 class="modal__title" id="modalServTitle">💆‍♀️ Gestión de Tratamientos (CRUD)</h2>
      
      <!-- Formulario para Crear/Editar Servicio -->
      <form class="modal__form" id="formServicioCrud" style="background: rgba(0,0,0,0.03); padding: 16px; border-radius: 8px; margin-bottom: 16px;">
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
              <option value="1">Hidratacion facial (Facial)</option>
              <option value="2">Limpieza facial (Facial)</option>
              <option value="3">Masajes corporales (Corporal)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="crudServDesc">Descripción</label>
            <input type="text" class="form-input" id="crudServDesc" placeholder="Breve descripción del procedimiento">
          </div>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 12px;">
          <button type="submit" class="btn btn--primary btn--sm" id="btnSaveServicio">Guardar Tratamiento</button>
          <button type="button" class="btn btn--outline btn--sm" id="btnCancelServicioEdit" style="display: none;">Cancelar Edición</button>
        </div>
      </form>

      <!-- Tabla de Servicios -->
      <h4 style="font-size: 0.95rem; margin-bottom: 8px;">Catálogo Actual</h4>
      <div class="modal__scroll" style="max-height: 240px;">
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
  </div>

  <!-- 6. Modal Gestión de Personal (CRUD SuperAdmin) -->
  <div class="modal" id="modalPersonal" role="dialog" aria-modal="true" aria-labelledby="modalPersTitle">
    <div class="modal__content modal__content--wide">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <h2 class="modal__title" id="modalPersTitle">👥 Gestión de Personal (CRUD)</h2>
      
      <!-- Formulario Empleado -->
      <form class="modal__form" id="formPersonalCrud" style="background: rgba(0,0,0,0.03); padding: 16px; border-radius: 8px; margin-bottom: 16px;">
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
            <input type="password" class="form-input" id="crudStaffPassword" placeholder="Mínimo 6 caracteres">
          </div>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 12px;">
          <button type="submit" class="btn btn--primary btn--sm" id="btnSaveStaff">Guardar Empleado</button>
          <button type="button" class="btn btn--outline btn--sm" id="btnCancelStaffEdit" style="display: none;">Cancelar Edición</button>
        </div>
      </form>

      <!-- Tabla de Personal -->
      <h4 style="font-size: 0.95rem; margin-bottom: 8px;">Equipo Registrado</h4>
      <div class="modal__scroll" style="max-height: 240px;">
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
  </div>

  <!-- 7. Modal Logs de Auditoría (SuperAdmin) -->
  <div class="modal" id="modalLogs" role="dialog" aria-modal="true" aria-labelledby="modalLogsTitle">
    <div class="modal__content modal__content--wide">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      <h2 class="modal__title" id="modalLogsTitle">📋 Logs de Auditoría del Sistema</h2>
      <p style="font-size: 0.85rem; color: #888; margin-bottom: 16px;">
        Registro inmutable de acciones realizadas por administradores, recepcionistas y especialistas.
      </p>

      <div class="modal__scroll" style="max-height: 380px;">
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
  </div>

  <!-- 8. Modal Generar Informe Ejecutivo Imprimible (HU8) -->
  <div class="modal" id="modalReporte" role="dialog" aria-modal="true" aria-labelledby="modalRepTitle">
    <div class="modal__content modal__content--wide" style="max-width: 860px;">
      <button class="modal__close" data-close-modal aria-label="Cerrar">&times;</button>
      
      <!-- Contenedor Imprimible del Informe -->
      <div class="printable-report" id="printableReportArea">
        
        <!-- Encabezado del Reporte -->
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

        <!-- Filtros del Reporte (no se imprimen) -->
        <div class="report-filters no-print" style="margin: 16px 0; padding: 12px; background: rgba(0,0,0,0.03); border-radius: 8px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
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

        <!-- KPIs del Reporte -->
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

        <!-- Secciones de Desglose -->
        <div class="report-breakdowns" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
          <!-- Desglose por Estado -->
          <div class="report-box">
            <h4 style="margin-bottom: 8px; font-size: 0.9rem;">Citas por Estado</h4>
            <div id="repEstadosContainer">
              <!-- Renderizado dinámicamente -->
            </div>
          </div>
          <!-- Desglose por Especialista -->
          <div class="report-box">
            <h4 style="margin-bottom: 8px; font-size: 0.9rem;">Rendimiento por Especialista</h4>
            <div id="repEspecialistasContainer">
              <!-- Renderizado dinámicamente -->
            </div>
          </div>
        </div>

        <!-- Desglose por Tratamiento -->
        <div class="report-box" style="margin-bottom: 20px;">
          <h4 style="margin-bottom: 8px; font-size: 0.9rem;">Tratamientos Más Solicitados</h4>
          <div id="repServiciosContainer">
            <!-- Renderizado dinámicamente -->
          </div>
        </div>

        <!-- Tabla de Citas del Período -->
        <div class="report-table-box">
          <h4 style="margin-bottom: 8px; font-size: 0.9rem;">Detalle de Reservas del Período</h4>
          <div class="modal__scroll" style="max-height: 240px;">
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

        <!-- Pie de página de informe para impresión -->
        <div class="report-footer print-only" style="display: none; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 12px; font-size: 0.75rem; color: #666; justify-content: space-between;">
          <span>Hanul Beauty - Sistema K-Beauty</span>
          <span>Confidencial - Uso Administrativo</span>
        </div>

      </div>
    </div>
  </div>

  <!-- Toast container -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- Tokens y variables de contexto -->
  <input type="hidden" id="dashCsrf" value="<?php echo csrf_token(); ?>">
  <input type="hidden" id="currentUserRole" value="<?php echo $userRole; ?>">
  <input type="hidden" id="currentUserId" value="<?php echo (int)$currentUser['id_usuario']; ?>">

  <script src="js/dashboard.js?v=<?php echo filemtime(__DIR__ . '/js/dashboard.js'); ?>"></script>
</body>
</html>
