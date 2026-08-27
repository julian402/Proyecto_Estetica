<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/Favorite.php';
require_once __DIR__ . '/../../models/Treatment.php';

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

$servicioId = (int) ($input['servicio_id'] ?? 0);
if ($servicioId <= 0) {
    json_response(['error' => 'Servicio invalido'], 422);
}

$servicio = Treatment::findById($servicioId);
if (!$servicio) {
    json_response(['error' => 'El servicio no existe'], 404);
}

$added = Favorite::toggle($_SESSION['user_id'], $servicioId);

json_response([
    'success' => true,
    'added'   => $added,
    'message' => $added ? 'Agregado a favoritos' : 'Eliminado de favoritos',
]);
