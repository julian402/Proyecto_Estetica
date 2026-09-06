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

// Tarea 14: Esteticistas reciben 403 expresamente
if ($rol === 4) {
    json_response(['error' => 'Los esteticistas no tienen permisos para cancelar citas'], 403);
}

$input = json_input();
$token = $input['csrf_token'] ?? '';

if (!verify_csrf($token)) {
    json_response(['error' => 'Token de seguridad invalido'], 403);
}

$reservaId = (int) ($input['reserva_id'] ?? 0);
$motivo    = trim($input['motivo'] ?? 'Cancelada por el usuario');

if ($reservaId <= 0) {
    json_response(['error' => 'Reserva invalida'], 422);
}

$reserva = Appointment::findById($reservaId);
if (!$reserva) {
    json_response(['error' => 'La cita no existe'], 404);
}

// Validar que solo Admin (2, 3) o Cliente dueno (1) puedan cancelar
if (!can_cancel_or_reschedule($user, $reserva)) {
    json_response(['error' => 'No tienes permiso para cancelar esta cita'], 403);
}

try {
    $cancelled = Appointment::cancel($reservaId, (int) $user['id_usuario'], $motivo);
} catch (\DomainException $e) {
    json_response(['error' => $e->getMessage()], 422);
} catch (\Throwable $e) {
    error_log('Error cancelando cita: ' . $e->getMessage());
    json_response(['error' => 'Error al cancelar la cita. Intenta de nuevo.'], 500);
}

if (!$cancelled) {
    json_response(['error' => 'No se pudo cancelar la cita'], 422);
}

// Despachar correo de cancelacion
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
    send_status_change_notification($appointmentData, $clientData, $reserva['id_estado'], 4);
} catch (\Throwable $e) {
    error_log('Error despachando correo de cancelacion: ' . $e->getMessage());
}

json_response(['success' => true, 'message' => 'Cita cancelada correctamente']);
