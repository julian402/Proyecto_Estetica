<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/Appointment.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

require_login();

$user = current_user();
$rol = (int) ($user['id_rol'] ?? 0);

// Solo SuperAdmin (2), Recepcionista (3) o Esteticista (4)
if (!in_array($rol, [2, 3, 4], true)) {
    json_response(['error' => 'No tienes permiso para ver esta informacion'], 403);
}

$filterDate        = $_GET['fecha'] ?? null;
$filterEstado      = !empty($_GET['estado']) ? (int) $_GET['estado'] : null;
$filterEsteticista = !empty($_GET['esteticista']) ? (int) $_GET['esteticista'] : null;
$filterServicio    = !empty($_GET['servicio']) ? (int) $_GET['servicio'] : (!empty($_GET['servicio_id']) ? (int) $_GET['servicio_id'] : null);
if ($rol === 4) {
    $filterEsteticista = (int) $user['id_usuario'];
}
$reservas = Appointment::getAll($filterDate, $filterEstado, $filterEsteticista, $filterServicio);
$stats    = Appointment::countByStatus();

json_response([
    'success'  => true,
    'reservas' => $reservas,
    'stats'    => $stats,
]);
