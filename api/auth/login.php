<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/User.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$token    = $input['csrf_token'] ?? '';

// Validar CSRF
if (!verify_csrf($token)) {
    json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
}

// Validar campos
if ($email === '' || $password === '') {
    json_response(['error' => 'Correo y contrasena son obligatorios'], 422);
}

// Buscar usuario (findByEmail ya filtra estado_cuenta = 1)
$user = User::findByEmail($email);

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_response(['error' => 'Correo o contrasena incorrectos'], 401);
}

// Crear sesion (regenera ID para prevenir session fixation)
login_session($user['id_usuario']);

json_response([
    'success' => true,
    'user'    => [
        'id'    => $user['id_usuario'],
        'name'  => $user['nombre'],
        'email' => $user['correo'],
    ],
    'csrf_token' => $_SESSION['csrf_token'],
]);
