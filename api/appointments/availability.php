<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/Appointment.php';
require_once __DIR__ . '/../../models/Treatment.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

$date       = $_GET['date'] ?? '';
$servicioId = (int) ($_GET['servicio_id'] ?? 0);
$esteticId  = (int) ($_GET['esteticista_id'] ?? 0);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $servicioId <= 0) {
    json_response(['error' => 'Parametros invalidos'], 422);
}

if ($date < date('Y-m-d')) {
    json_response(['success' => true, 'slots' => []]);
}

$servicio = Treatment::findById($servicioId);
if (!$servicio) {
    json_response(['error' => 'Servicio no encontrado'], 404);
}

$slots = Appointment::getAvailableSlots($date, (int) $servicio['duracion_minutos'], $esteticId ?: null);

if ($date === date('Y-m-d')) {
    $now = new DateTime('now');
    $slots = array_values(array_filter($slots, function ($label) use ($now, $date) {
        $dt = date_create_from_format('Y-m-d g:i a', $date . ' ' . strtolower($label));
        return $dt && $dt > $now;
    }));
}

json_response(['success' => true, 'slots' => $slots]);
