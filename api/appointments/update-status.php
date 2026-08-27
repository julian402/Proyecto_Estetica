<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/Appointment.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

require_login();

$user = current_user();
$rol = (int) ($user['id_rol'] ?? 0);

if (!in_array($rol, [2, 3, 4])) {
    json_response(['error' => 'No tienes permiso para realizar esta accion'], 403);
}

$input = json_input();

$reservaId   = (int) ($input['reserva_id'] ?? 0);
$nuevoEstado = (int) ($input['estado_id'] ?? 0);
$token       = $input['csrf_token'] ?? '';

if (!verify_csrf($token)) {
    json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
}

if ($reservaId <= 0 || $nuevoEstado <= 0 || $nuevoEstado > 6) {
    json_response(['error' => 'Datos invalidos'], 422);
}

try {
    $updated = Appointment::updateStatus($reservaId, $nuevoEstado);
} catch (\PDOException $e) {
    json_response(['error' => 'Error al actualizar el estado'], 500);
}

if (!$updated) {
    json_response(['error' => 'No se encontro la reserva o ya tenia ese estado'], 404);
}

$estados = ['', 'Pendiente', 'Confirmada', 'Completada', 'Cancelada', 'Reasignada', 'No_Show'];

json_response([
    'success' => true,
    'message' => 'Estado actualizado a ' . ($estados[$nuevoEstado] ?? 'desconocido'),
]);
