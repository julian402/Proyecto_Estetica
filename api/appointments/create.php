<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/Appointment.php';
require_once __DIR__ . '/../../models/Treatment.php';
require_once __DIR__ . '/../../models/User.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

require_login();

$input = json_input();

$requestingUser = current_user();
if (!$requestingUser || (int) $requestingUser['id_rol'] !== 1) {
    json_response(['error' => 'Solo una cuenta de cliente puede crear reservas'], 403);
}

$servicioId    = (int) ($input['servicio_id'] ?? 0);
$esteticId     = (int) ($input['esteticista_id'] ?? 0);
$dateRaw       = $input['date'] ?? '';
$timeRaw       = $input['time'] ?? '';
$token         = $input['csrf_token'] ?? '';

if (!is_string($dateRaw) || !is_string($timeRaw) || !is_string($token)) {
    json_response(['error' => 'Datos de reserva invalidos'], 422);
}
$date = trim($dateRaw);
$time = trim($timeRaw);

// Validar CSRF
if (!verify_csrf($token)) {
    json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
}

// Validar campos
if ($servicioId <= 0 || $date === '' || $time === '') {
    json_response(['error' => 'Todos los campos son obligatorios'], 422);
}

// Verificar que el servicio existe
$servicio = Treatment::findById($servicioId);
if (!$servicio) {
    json_response(['error' => 'El servicio seleccionado no existe'], 404);
}

// Si esteticista_id es 0 (aleatorio), asignar uno al azar
if ($esteticId <= 0) {
    $esteticistas = User::getEsteticistas();
    if (empty($esteticistas)) {
        json_response(['error' => 'No hay esteticistas disponibles'], 422);
    }
    $esteticId = $esteticistas[array_rand($esteticistas)]['id_usuario'];
} else {
    // Verificar que el esteticista existe y tiene rol 4
    $est = User::findById($esteticId);
    if (!$est || $est['id_rol'] != 4) {
        json_response(['error' => 'El esteticista seleccionado no es valido'], 422);
    }
}

// Validar formato de fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    json_response(['error' => 'Formato de fecha invalido'], 422);
}

// Construir fecha_hora_inicio y fecha_hora_fin
// Convertir hora tipo "10:00 am" a formato 24h
$timeClean = strtolower(trim($time));
$dateTime = date_create_from_format('Y-m-d g:i a', $date . ' ' . $timeClean);
if (!$dateTime) {
    // Intentar formato 24h
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

// Calcular fin sumando la duracion del servicio
$dateTime->modify('+' . (int) $servicio['duracion_minutos'] . ' minutes');
$fechaFin = $dateTime->format('Y-m-d H:i:s');

// Crear la reserva (el trigger de MySQL validara choques de horario)
try {
    $reservaId = Appointment::create($_SESSION['user_id'], $esteticId, $servicioId, $fechaInicio, $fechaFin);
} catch (\PDOException $e) {
    // El trigger lanza SQLSTATE 45000 si hay choque de horario
    if ($e->getCode() === '45000') {
        json_response(['error' => $e->getMessage()], 409);
    }
    json_response(['error' => 'Error al crear la reserva. Intenta de nuevo.'], 500);
}

json_response([
    'success'  => true,
    'reserva'  => [
        'id'              => $reservaId,
        'servicio'        => $servicio['nombre_servicio'],
        'fecha_inicio'    => $fechaInicio,
        'fecha_fin'       => $fechaFin,
        'estado'          => 'Pendiente',
    ],
]);
