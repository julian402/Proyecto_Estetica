<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/Appointment.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

require_login();

$reservaId = !empty($_GET['reserva_id']) ? (int) $_GET['reserva_id'] : (!empty($_GET['id']) ? (int) $_GET['id'] : 0);
if ($reservaId <= 0) {
    json_response(['error' => 'ID de reserva invalido'], 422);
}

$reserva = Appointment::findById($reservaId);
if (!$reserva) {
    json_response(['error' => 'Reserva no encontrada'], 404);
}

$user = current_user();
$rol  = (int) ($user['id_rol'] ?? 0);
$userId = (int) ($user['id_usuario'] ?? 0);

// Control de permisos: Admin/Recep pueden ver todo; Cliente solo sus citas; Esteticista solo sus asignadas
$isOwnerClient = ($rol === 1 && (int) $reserva['id_cliente'] === $userId);
$isAssignedEst = ($rol === 4 && (int) $reserva['id_esteticista'] === $userId);
$isAdmin       = in_array($rol, [2, 3], true);

if (!$isAdmin && !$isOwnerClient && !$isAssignedEst) {
    json_response(['error' => 'No tienes permiso para consultar el historial de esta cita'], 403);
}

$history = Appointment::getHistory($reservaId);

json_response([
    'success'    => true,
    'reserva_id' => $reservaId,
    'servicio'   => $reserva['nombre_servicio'],
    'historial'  => $history,
]);
