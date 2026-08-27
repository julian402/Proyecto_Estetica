<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/Appointment.php';
require_once __DIR__ . '/../../models/Treatment.php';
require_once __DIR__ . '/../../models/User.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

$input = json_input();

$token = $input['csrf_token'] ?? '';
if (!verify_csrf($token)) {
    json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
}

$nombre    = trim($input['nombre'] ?? '');
$correo    = trim($input['correo'] ?? '');
$telefono  = trim($input['telefono'] ?? '');
$servicioId = (int) ($input['servicio_id'] ?? 0);
$esteticId  = (int) ($input['esteticista_id'] ?? 0);
$dateRaw    = $input['date'] ?? '';
$timeRaw    = $input['time'] ?? '';

if (!is_string($dateRaw) || !is_string($timeRaw)) {
    json_response(['error' => 'Datos de reserva invalidos'], 422);
}
$date = trim($dateRaw);
$time = trim($timeRaw);

if ($nombre === '' || $correo === '') {
    json_response(['error' => 'Nombre y correo son obligatorios'], 422);
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Correo electronico invalido'], 422);
}

if ($servicioId <= 0 || $date === '' || $time === '') {
    json_response(['error' => 'Todos los campos de la cita son obligatorios'], 422);
}

$servicio = Treatment::findById($servicioId);
if (!$servicio) {
    json_response(['error' => 'El servicio seleccionado no existe'], 404);
}

if ($esteticId <= 0) {
    $esteticistas = User::getEsteticistas();
    if (empty($esteticistas)) {
        json_response(['error' => 'No hay esteticistas disponibles'], 422);
    }
    $esteticId = $esteticistas[array_rand($esteticistas)]['id_usuario'];
} else {
    $est = User::findById($esteticId);
    if (!$est || $est['id_rol'] != 4) {
        json_response(['error' => 'El esteticista seleccionado no es valido'], 422);
    }
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
$dateErrors = DateTime::getLastErrors();
if (is_array($dateErrors) && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) {
    json_response(['error' => 'Fecha u hora invalida'], 422);
}
if ($dateTime <= new DateTime('now')) {
    json_response(['error' => 'La reserva debe ser para una fecha futura'], 422);
}

$fechaInicio = $dateTime->format('Y-m-d H:i:s');
$dateTime->modify('+' . (int) $servicio['duracion_minutos'] . ' minutes');
$fechaFin = $dateTime->format('Y-m-d H:i:s');

try {
    $clienteId = User::findOrCreateGuest($nombre, $correo, $telefono ?: null);
} catch (\PDOException $e) {
    json_response(['error' => 'Error al procesar tus datos. Intenta de nuevo.'], 500);
}

try {
    $reservaId = Appointment::create($clienteId, $esteticId, $servicioId, $fechaInicio, $fechaFin);
} catch (\PDOException $e) {
    if ($e->getCode() === '45000') {
        json_response(['error' => $e->getMessage()], 409);
    }
    json_response(['error' => 'Error al crear la reserva. Intenta de nuevo.'], 500);
}

json_response([
    'success' => true,
    'reserva' => [
        'id'           => $reservaId,
        'servicio'     => $servicio['nombre_servicio'],
        'fecha_inicio' => $fechaInicio,
        'fecha_fin'    => $fechaFin,
        'estado'       => 'Pendiente',
    ],
]);
