<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Appointment.php';

start_session();

// Solo usuarios logueados con rol admin (2), recepcion (3) o esteticista (4)
$currentUser = current_user();
if (!$currentUser || !in_array((int) $currentUser['id_rol'], [2, 3, 4])) {
    header('Location: index.php');
    exit;
}

$esteticistas = User::getEsteticistas();
$stats        = Appointment::countByStatus();
$totalCitas   = array_sum($stats);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Hanul Beauty</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>">
</head>
<body>

  <header class="header" id="header">
    <div class="container header__inner">
      <a href="index.php" class="logo">
        <span class="logo__dot"></span>
        <span class="logo__text">Hanul Beauty</span>
      </a>
      <div class="header__actions">
        <span style="font-size: 0.85rem; color: #888; margin-right: 8px;">
          <?php echo sanitize($currentUser['nombre']); ?>
        </span>
        <button class="btn btn--primary btn--sm" id="dashLogoutBtn">Cerrar sesion</button>
      </div>
    </div>
  </header>

  <section class="dashboard">
    <div class="container">
      <div class="dashboard__header">
        <div>
          <h1 class="section__title" style="margin-bottom: 4px;">Panel de Reservas</h1>
          <p style="color: #888; font-size: 0.85rem;">Gestiona las citas de Hanul Beauty</p>
        </div>
      </div>

      <!-- Stats -->
      <div class="dashboard__stats">
        <div class="dashboard__stat-card">
          <span class="dashboard__stat-number"><?php echo $totalCitas; ?></span>
          <span class="dashboard__stat-label">Total citas</span>
        </div>
        <div class="dashboard__stat-card">
          <span class="dashboard__stat-number"><?php echo $stats['Pendiente'] ?? 0; ?></span>
          <span class="dashboard__stat-label">Pendientes</span>
        </div>
        <div class="dashboard__stat-card">
          <span class="dashboard__stat-number"><?php echo $stats['Confirmada'] ?? 0; ?></span>
          <span class="dashboard__stat-label">Confirmadas</span>
        </div>
        <div class="dashboard__stat-card">
          <span class="dashboard__stat-number"><?php echo ($stats['Completada'] ?? 0); ?></span>
          <span class="dashboard__stat-label">Completadas</span>
        </div>
      </div>

      <!-- Filtros -->
      <div class="dashboard__filters" style="margin-bottom: 20px;">
        <input type="date" class="form-input" id="filterFecha" placeholder="Filtrar por fecha">
        <select class="form-select" id="filterEstado">
          <option value="">Todos los estados</option>
          <option value="1">Pendiente</option>
          <option value="2">Confirmada</option>
          <option value="3">Completada</option>
          <option value="4">Cancelada</option>
          <option value="5">Reasignada</option>
          <option value="6">No Show</option>
        </select>
        <select class="form-select" id="filterEsteticista">
          <option value="">Todos los esteticistas</option>
          <?php foreach ($esteticistas as $est): ?>
          <option value="<?php echo (int) $est['id_usuario']; ?>"><?php echo sanitize($est['nombre']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Tabla de reservas -->
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
              <th>Cambiar estado</th>
            </tr>
          </thead>
          <tbody id="reservasBody">
            <tr><td colspan="8" class="dashboard__empty">Cargando reservas...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- Toast container -->
  <div class="toast-container" id="toastContainer"></div>

  <input type="hidden" id="dashCsrf" value="<?php echo csrf_token(); ?>">
  <script src="js/dashboard.js?v=<?php echo filemtime(__DIR__ . '/js/dashboard.js'); ?>"></script>
</body>
</html>
