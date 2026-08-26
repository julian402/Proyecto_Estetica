<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/User.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

$input = json_input();

$nameRaw  = $input['name'] ?? '';
$emailRaw = $input['email'] ?? '';
$password = $input['password'] ?? '';
$confirm  = $input['password_confirm'] ?? '';
$token    = $input['csrf_token'] ?? '';

if (!is_string($nameRaw) || !is_string($emailRaw) || !is_string($password)
    || !is_string($confirm) || !is_string($token)) {
    json_response(['error' => 'Datos de registro invalidos'], 422);
}
$name = trim($nameRaw);
$email = strtolower(trim($emailRaw));

// Validar CSRF
if (!verify_csrf($token)) {
    json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
}

// Validar campos
if ($name === '' || $email === '' || $password === '' || $confirm === '') {
    json_response(['error' => 'Todos los campos son obligatorios'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'El correo no es valido'], 422);
}

if (mb_strlen($name) > 100 || strlen($email) > 150) {
    json_response(['error' => 'Nombre o correo demasiado largo'], 422);
}

if (strlen($password) < 12 || strlen($password) > 128) {
    json_response(['error' => 'La contrasena debe tener entre 12 y 128 caracteres'], 422);
}

if ($password !== $confirm) {
    json_response(['error' => 'Las contrasenas no coinciden'], 422);
}

// Verificar que el correo no exista
if (User::findByEmail($email)) {
    json_response(['error' => 'Ya existe una cuenta con ese correo'], 409);
}

// Crear usuario (rol Cliente = 1)
$userId = User::create($name, $email, $password);

// Iniciar sesion automaticamente (regenera ID para prevenir session fixation)
login_session($userId);

json_response([
    'success' => true,
    'user'    => [
        'id'    => $userId,
        'name'  => $name,
        'email' => $email,
    ],
    'csrf_token' => $_SESSION['csrf_token'],
]);
