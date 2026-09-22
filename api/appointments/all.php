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
$filterCliente     = isset($_GET['cliente']) ? mb_substr(trim((string) $_GET['cliente']), 0, 100) : null;
$page              = max(1, (int) ($_GET['page'] ?? 1));
$limit             = min(100, max(1, (int) ($_GET['limit'] ?? 10)));
if ($rol === 4) {
    $filterEsteticista = (int) $user['id_usuario'];
}
$result = Appointment::getPaginated($page, $limit, $filterDate, $filterEstado, $filterEsteticista, $filterServicio, $filterCliente);
$stats    = Appointment::countByStatus();

json_response([
    'success'  => true,
    'reservas' => $result['reservas'],
    'stats'    => $stats,
    'pagination' => [
        'page' => $result['page'],
        'limit' => $result['limit'],
        'total' => $result['total'],
        'total_pages' => $result['total_pages'],
    ],
]);
