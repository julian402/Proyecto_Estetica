<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/User.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

$input = json_input();

$emailRaw = $input['email'] ?? '';
$password = $input['password'] ?? '';
$token    = $input['csrf_token'] ?? '';

if (!is_string($emailRaw) || !is_string($password) || !is_string($token)) {
    json_response(['error' => 'Datos de acceso invalidos'], 422);
}
$email = strtolower(trim($emailRaw));

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

$attempts = $_SESSION['login_attempts'] ?? [];
$attempts = array_values(array_filter($attempts, fn($time) => is_int($time) && $time > time() - 900));
if (count($attempts) >= 10) {
    json_response(['error' => 'Demasiados intentos. Espera 15 minutos.'], 429);
}

if (!$user || !password_verify($password, $user['password_hash'])) {
    $attempts[] = time();
    $_SESSION['login_attempts'] = $attempts;
    json_response(['error' => 'Correo o contrasena incorrectos'], 401);
}

unset($_SESSION['login_attempts']);

// Crear sesion (regenera ID para prevenir session fixation)
login_session($user['id_usuario']);

json_response([
    'success' => true,
    'user'    => [
        'id'    => $user['id_usuario'],
        'name'  => $user['nombre'],
        'email' => $user['correo'],
        'role'  => (int) $user['id_rol'],
    ],
    'csrf_token' => $_SESSION['csrf_token'],
]);
