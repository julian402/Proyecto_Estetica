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

$input = json_input();

$token = $input['csrf_token'] ?? '';
if (!verify_csrf($token)) {
    json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
}

// 1. Resolver quien es el cliente de la reserva
$isGuest = true;
$currentUser = current_user();

// El personal (SuperAdmin 2, Recepcion 3, Esteticista 4) agenda PARA un tercero:
// los datos del formulario mandan, no los de la sesion.
$isStaff = $currentUser && in_array((int) ($currentUser['id_rol'] ?? 0), [2, 3, 4], true);

if ($currentUser && !$isStaff) {
    $clienteId = (int) $currentUser['id_usuario'];
    $nombre    = trim($input['nombre'] ?? $currentUser['nombre']);
    $correo    = strtolower(trim($input['correo'] ?? $currentUser['correo']));
    $telefono  = trim($input['telefono'] ?? ($currentUser['telefono'] ?? ''));
    $isGuest   = !empty($currentUser['es_invitado']);
} else {
    $nombre    = trim($input['nombre'] ?? '');
    $correo    = strtolower(trim($input['correo'] ?? ''));
    $telefono  = trim($input['telefono'] ?? '');
}

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

// Tarea 19 y 22: Validar telefono obligatorio
if ($telefono === '') {
    json_response(['error' => 'El telefono es obligatorio para coordinar tu cita'], 422);
}
if (!preg_match('/^[0-9+\s\-()]{7,20}$/', $telefono)) {
    json_response(['error' => 'Formato de telefono invalido (debe contener entre 7 y 20 digitos)'], 422);
}

if ($servicioId <= 0 || $date === '' || $time === '') {
    json_response(['error' => 'Todos los campos de la cita son obligatorios'], 422);
}

$servicio = Treatment::findById($servicioId);
if (!$servicio || empty($servicio['activo'])) {
    json_response(['error' => 'El servicio seleccionado no existe o no esta disponible'], 404);
}

// Resolucion de especialista
if ($esteticId <= 0) {
    $esteticistas = User::getEsteticistas();
    if (empty($esteticistas)) {
        json_response(['error' => 'No hay esteticistas disponibles'], 422);
    }
    $esteticId = (int) $esteticistas[array_rand($esteticistas)]['id_usuario'];
} else {
    $est = User::findById($esteticId);
    if (!$est || (int)$est['id_rol'] !== 4 || empty($est['estado_cuenta'])) {
        json_response(['error' => 'El especialista seleccionado no es valido o no esta activo'], 422);
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
    json_response(['error' => 'La reserva debe ser para una fecha y hora futura'], 422);
}

$fechaInicio = $dateTime->format('Y-m-d H:i:s');
$dateTime->modify('+' . (int) $servicio['duracion_minutos'] . ' minutes');
$fechaFin = $dateTime->format('Y-m-d H:i:s');

// 2. Gestion o creacion de usuario si no estaba autenticado
if (!$currentUser || $isStaff) {
    try {
        $clienteId = User::findOrCreateGuest($nombre, $correo, $telefono);
    } catch (\Throwable $e) {
        error_log('Error creando cliente invitado: ' . $e->getMessage());
        json_response(['error' => 'Error al procesar tus datos de contacto. Intenta de nuevo.'], 500);
    }
}

// 3. Tarea 9: Validar que el cliente no tenga traslape en ese mismo horario
if (Appointment::hasClientConflict($clienteId, $fechaInicio, $fechaFin)) {
    json_response([
        'error' => 'Ya tienes otra cita agendada en ese mismo horario. Por favor selecciona otra hora para tu tratamiento.'
    ], 409);
}

// 4. Tarea 13: Bloquear espacio de inmediato tras crear la reserva
try {
    $reservaId = Appointment::create($clienteId, $esteticId, $servicioId, $fechaInicio, $fechaFin);
} catch (\DomainException $e) {
    json_response(['error' => $e->getMessage()], 409);
} catch (\PDOException $e) {
    if ($e->getCode() === '45000') {
        json_response(['error' => $e->getMessage()], 409);
    }
    error_log('Error al crear cita: ' . $e->getMessage());
    json_response(['error' => 'No fue posible confirmar el espacio. Puede que haya sido reservado recientemente.'], 409);
}

$esteticistaData = User::findById($esteticId);
$especialistaNombre = $esteticistaData['nombre'] ?? 'Especialista Hanul';

// 5. Tarea 11: Registrar en auditoria
log_audit(
    $clienteId,
    'CREATE_APPOINTMENT',
    'reservas',
    $reservaId,
    "Nueva cita para {$servicio['nombre_servicio']} con {$especialistaNombre} el {$fechaInicio}"
);

// 6. Tarea 2: Despachar correo transaccional HU03
$appointmentData = [
    'id_reserva'         => $reservaId,
    'nombre_servicio'    => $servicio['nombre_servicio'],
    'nombre_esteticista' => $especialistaNombre,
    'fecha_inicio'       => $fechaInicio,
    'fecha_fin'          => $fechaFin,
    'duracion_minutos'   => $servicio['duracion_minutos'],
    'precio'             => $servicio['precio'],
];
$clientData = [
    'nombre'   => $nombre,
    'correo'   => $correo,
    'telefono' => $telefono,
];

try {
    send_appointment_confirmation($appointmentData, $clientData, $isGuest);
} catch (\Throwable $e) {
    error_log('Error despachando correo HU03: ' . $e->getMessage());
}

json_response([
    'success' => true,
    'message' => '¡Tu cita ha sido reservada con exito! Revisa tu correo electronico con la confirmacion.',
    'reserva' => [
        'id'           => $reservaId,
        'servicio'     => $servicio['nombre_servicio'],
        'esteticista'  => $especialistaNombre,
        'fecha_inicio' => $fechaInicio,
        'fecha_fin'    => $fechaFin,
        'estado'       => 'Pendiente',
    ],
]);
