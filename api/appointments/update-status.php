<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/audit.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../models/Appointment.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

require_login();

$user = current_user();
$rol = (int) ($user['id_rol'] ?? 0);

if (!in_array($rol, [2, 3, 4], true)) {
    json_response(['error' => 'No tienes permiso para realizar esta accion'], 403);
}

$input = json_input();

$reservaId   = (int) ($input['reserva_id'] ?? 0);
$nuevoEstado = (int) ($input['estado_id'] ?? 0);
$motivo      = trim($input['motivo'] ?? '');
$token       = $input['csrf_token'] ?? '';

if (!verify_csrf($token)) {
    json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
}

if ($reservaId <= 0 || $nuevoEstado <= 0 || $nuevoEstado > 6) {
    json_response(['error' => 'Datos invalidos'], 422);
}

$reserva = Appointment::findById($reservaId);
if (!$reserva) {
    json_response(['error' => 'No se encontro la reserva solicitada'], 404);
}

// Tarea 14: Control de permisos. Esteticistas (4) no pueden cancelar citas
if ($rol === 4 && $nuevoEstado === 4) {
    json_response(['error' => 'Los esteticistas no tienen permisos para cancelar citas'], 403);
}

// Si es esteticista, solo puede cambiar el estado de sus propias citas
if ($rol === 4 && (int) $reserva['id_esteticista'] !== (int) $user['id_usuario']) {
    json_response(['error' => 'Solo puedes modificar el estado de tus propias citas asignadas'], 403);
}

$oldStatus = (int) $reserva['id_estado'];

try {
    $updated = Appointment::updateStatus($reservaId, $nuevoEstado, (int) $user['id_usuario'], $motivo);
} catch (\Throwable $e) {
    error_log('Error al actualizar el estado: ' . $e->getMessage());
    json_response(['error' => 'Error al actualizar el estado'], 500);
}

if (!$updated) {
    json_response(['error' => 'No se encontro la reserva o ya se encontraba en ese estado'], 404);
}

// Tarea 7: Despachar correo de cambio de estado
if ($oldStatus !== $nuevoEstado) {
    try {
        $appointmentData = [
            'id_reserva'      => $reserva['id_reserva'],
            'nombre_servicio' => $reserva['nombre_servicio'],
            'fecha_inicio'    => $reserva['fecha_hora_inicio'],
        ];
        $clientData = [
            'nombre' => $reserva['nombre_cliente'],
            'correo' => $reserva['correo_cliente'],
        ];
        send_status_change_notification($appointmentData, $clientData, $oldStatus, $nuevoEstado);
    } catch (\Throwable $e) {
        error_log('Error enviando notificacion de cambio de estado: ' . $e->getMessage());
    }
}

$estados = ['', 'Pendiente', 'Confirmada', 'Completada', 'Cancelada', 'Reasignada', 'No_Show'];

json_response([
    'success' => true,
    'message' => 'Estado actualizado a ' . ($estados[$nuevoEstado] ?? 'desconocido'),
]);
