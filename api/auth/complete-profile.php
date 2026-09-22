<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/audit.php';
require_once __DIR__ . '/../../models/User.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

$input = json_input();

$emailRaw = $input['email'] ?? '';
$password = $input['password'] ?? '';
$confirm  = $input['password_confirm'] ?? '';
$token    = $input['csrf_token'] ?? '';
$nameRaw  = $input['name'] ?? null;
$phoneRaw = $input['phone'] ?? $input['telefono'] ?? null;

if (!is_string($emailRaw) || !is_string($password) || !is_string($confirm) || !is_string($token)) {
    json_response(['error' => 'Datos invalidos'], 422);
}

if (!verify_csrf($token)) {
    json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
}

$email = strtolower(trim($emailRaw));
$name  = is_string($nameRaw) && trim($nameRaw) !== '' ? trim($nameRaw) : null;
$phone = is_string($phoneRaw) && trim($phoneRaw) !== '' ? trim($phoneRaw) : null;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Correo electronico invalido'], 422);
}

$user = User::findByEmailAny($email);
if (!$user) {
    json_response(['error' => 'No se encontro ninguna cuenta o reserva previa con este correo electronico'], 404);
}

// Validar longitud de contrasena (min 8 caracteres)
if (strlen($password) < 8 || strlen($password) > 128) {
    json_response(['error' => 'La contrasena debe tener entre 8 y 128 caracteres'], 422);
}

if ($password !== $confirm) {
    json_response(['error' => 'Las contrasenas no coinciden'], 422);
}

// Completar perfil y establecer contrasena definitiva
$userId = User::completeGuestProfile($email, $password, $name, $phone);

// Iniciar sesion
login_session($userId);
log_audit($userId, 'COMPLETE_GUEST_PROFILE', 'usuarios', $userId, 'Invitado completo perfil activando su cuenta');

$updatedUser = User::findById($userId);

json_response([
    'success' => true,
    'message' => '¡Tu perfil ha sido completado con exito! Ya puedes gestionar tus citas.',
    'user'    => [
        'id'    => $userId,
        'name'  => $updatedUser['nombre'],
        'email' => $updatedUser['correo'],
        'phone' => $updatedUser['telefono'] ?? '',
    ],
    'csrf_token' => $_SESSION['csrf_token'],
]);
