<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/audit.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../models/Appointment.php';
require_once __DIR__ . '/../../models/Treatment.php';
require_once __DIR__ . '/../../models/User.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

require_login();

$user = current_user();
$rol = (int) ($user['id_rol'] ?? 0);

// Tarea 14: Esteticistas estan estrictamente bloqueados
if ($rol === 4) {
    json_response(['error' => 'Los esteticistas no tienen permisos para reprogramar citas'], 403);
}

$input = json_input();
$token = $input['csrf_token'] ?? '';

if (!verify_csrf($token)) {
    json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
}

$reservaId = (int) ($input['reserva_id'] ?? 0);
$dateRaw   = $input['date'] ?? $input['nueva_fecha'] ?? '';
$timeRaw   = $input['time'] ?? $input['nueva_hora'] ?? '';
$motivo    = trim($input['motivo'] ?? 'Reprogramacion solicitada');

if ($reservaId <= 0 || !is_string($dateRaw) || !is_string($timeRaw)) {
    json_response(['error' => 'Datos de reprogramacion invalidos'], 422);
}

$date = trim($dateRaw);
$time = trim($timeRaw);

if ($date === '' || $time === '') {
    json_response(['error' => 'La fecha y la hora son obligatorias'], 422);
}

$reserva = Appointment::findById($reservaId);
if (!$reserva) {
    json_response(['error' => 'La cita no existe'], 404);
}

// Permiso: Admin [2, 3] o Cliente dueno [1]
if (!can_cancel_or_reschedule($user, $reserva)) {
    json_response(['error' => 'No tienes permiso para reprogramar esta cita'], 403);
}

// Estado actual de la cita
if (in_array((int) $reserva['id_estado'], [3, 4], true)) {
    json_response(['error' => 'No se puede reprogramar una cita cancelada o completada'], 422);
}

$esteticInput = $input['esteticista_id'] ?? $input['id_esteticista'] ?? null;
$newEsteticId = !empty($esteticInput) ? (int) $esteticInput : (int) $reserva['id_esteticista'];

// Validar nuevo esteticista
$est = User::findById($newEsteticId);
if (!$est || (int) $est['id_rol'] !== 4 || empty($est['estado_cuenta'])) {
    json_response(['error' => 'El especialista seleccionado no es valido'], 422);
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    json_response(['error' => 'Formato de fecha invalido'], 422);
}

$timeClean = strtolower(trim($time));
$dateTime = date_create_from_format('Y-m-d g:i a', $date . ' ' . $timeClean);
if (!$dateTime) {
    $dateTime = date_create_from_format('Y-m-d H:i', $date . ' ' . $time);
}
if (!$dateTime) {
    json_response(['error' => 'Formato de hora invalido'], 422);
}
if ($dateTime <= new DateTime('now')) {
    json_response(['error' => 'La nueva fecha y hora debe ser futura'], 422);
}

$duracionMinutos = (int) ($reserva['duracion_minutos'] ?? 60);
$fechaInicio = $dateTime->format('Y-m-d H:i:s');
$dateTime->modify("+{$duracionMinutos} minutes");
$fechaFin = $dateTime->format('Y-m-d H:i:s');

try {
    Appointment::reschedule($reservaId, $fechaInicio, $fechaFin, $newEsteticId, (int) $user['id_usuario'], $motivo);
} catch (\DomainException $e) {
    json_response(['error' => $e->getMessage()], 409);
} catch (\Throwable $e) {
    error_log('Error reprogramando cita: ' . $e->getMessage());
    json_response(['error' => 'No fue posible reprogramar la cita. Verifica la disponibilidad del horario.'], 409);
}

// Tarea 24: Despachar correo de reprogramacion
try {
    $appointmentData = [
        'id_reserva'      => $reservaId,
        'nombre_servicio' => $reserva['nombre_servicio'],
        'fecha_inicio'    => $reserva['fecha_hora_inicio'],
    ];
    $clientData = [
        'nombre' => $reserva['nombre_cliente'],
        'correo' => $reserva['correo_cliente'],
    ];
    $newSlot = [
        'fecha_inicio' => $fechaInicio,
        'fecha_fin'    => $fechaFin,
    ];
    send_reschedule_notification($appointmentData, $clientData, $newSlot);
} catch (\Throwable $e) {
    error_log('Error despachando correo de reprogramacion: ' . $e->getMessage());
}

json_response([
    'success' => true,
    'message' => 'Cita reprogramada exitosamente.',
    'reserva' => [
        'id'           => $reservaId,
        'fecha_inicio' => $fechaInicio,
        'fecha_fin'    => $fechaFin,
        'esteticista'  => $est['nombre'],
    ],
]);
