<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/Appointment.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

require_login();

$input = json_input();
$token = $input['csrf_token'] ?? '';

if (!verify_csrf($token)) {
    json_response(['error' => 'Token de seguridad invalido'], 403);
}

$reservaId = (int) ($input['reserva_id'] ?? 0);
if ($reservaId <= 0) {
    json_response(['error' => 'Reserva invalida'], 422);
}

$cancelled = Appointment::cancel($reservaId, $_SESSION['user_id']);

if (!$cancelled) {
    json_response(['error' => 'No se pudo cancelar. Solo se cancelan citas pendientes.'], 422);
}

json_response(['success' => true, 'message' => 'Cita cancelada correctamente']);
