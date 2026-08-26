<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/Appointment.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

require_login();

$reservas = Appointment::getByUser($_SESSION['user_id']);

json_response([
    'success'  => true,
    'reservas' => $reservas,
]);
