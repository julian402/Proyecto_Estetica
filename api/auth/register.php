<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/audit.php';
require_once __DIR__ . '/../../includes/mailer.php';
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
$phoneRaw = $input['phone'] ?? $input['telefono'] ?? null;

if (!is_string($nameRaw) || !is_string($emailRaw) || !is_string($password)
    || !is_string($confirm) || !is_string($token)) {
    json_response(['error' => 'Datos de registro invalidos'], 422);
}
$name = trim($nameRaw);
$email = strtolower(trim($emailRaw));
$phone = is_string($phoneRaw) ? trim($phoneRaw) : null;

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

// Tarea 21: Reducir contrasena a minimo 8 caracteres
if (strlen($password) < 8 || strlen($password) > 128) {
    json_response(['error' => 'La contrasena debe tener entre 8 y 128 caracteres'], 422);
}

if ($password !== $confirm) {
    json_response(['error' => 'Las contrasenas no coinciden'], 422);
}

// Verificar si el correo ya existe
$existingUser = User::findByEmailAny($email);
if ($existingUser) {
    // Si fue creado como invitado, actualizar sus datos y contrasena en lugar de retornar error 409
    if (!empty($existingUser['es_invitado'])) {
        $userId = User::completeGuestProfile($email, $password, $name, $phone);
        login_session($userId);
        log_audit($userId, 'REGISTER_FROM_GUEST', 'usuarios', $userId, 'Perfil completado desde cuenta invitada previa');

        json_response([
            'success' => true,
            'message' => '¡Bienvenido! Tu cuenta ha sido activada con tus datos.',
            'user'    => [
                'id'    => $userId,
                'name'  => $name,
                'email' => $email,
            ],
            'csrf_token' => $_SESSION['csrf_token'],
        ]);
    } else {
        json_response(['error' => 'Ya existe una cuenta con ese correo'], 409);
    }
}

// Crear nuevo usuario (rol Cliente = 1)
$userId = User::create($name, $email, $password, $phone);

// Iniciar sesion automaticamente
login_session($userId);
log_audit($userId, 'REGISTER', 'usuarios', $userId, 'Nuevo registro de cliente');

// Correo de bienvenida (nunca debe romper el registro)
try {
    send_welcome_email($name, $email);
} catch (Throwable $e) {
    error_log('Error despachando correo de bienvenida: ' . $e->getMessage());
}

json_response([
    'success' => true,
    'user'    => [
        'id'    => $userId,
        'name'  => $name,
        'email' => $email,
    ],
    'csrf_token' => $_SESSION['csrf_token'],
]);
