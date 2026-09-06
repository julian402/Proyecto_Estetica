<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/audit.php';
require_once __DIR__ . '/../../models/User.php';

start_session();
require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_response([
        'success' => true,
        'user'    => [
            'id'    => $user['id_usuario'],
            'name'  => $user['nombre'],
            'email' => $user['correo'],
            'phone' => $user['telefono'] ?? '',
            'rol'   => $user['id_rol'],
        ],
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

$input = json_input();
$token = $input['csrf_token'] ?? '';

if (!verify_csrf($token)) {
    json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
}

$nombre   = trim($input['name'] ?? '');
$telefono = trim($input['phone'] ?? $input['telefono'] ?? '');

if ($nombre === '') {
    json_response(['error' => 'El nombre es obligatorio'], 422);
}

$db = getDB();

$currentPassword = $input['current_password'] ?? '';
$newPassword     = $input['new_password'] ?? '';

if ($newPassword !== '') {
    // Tarea 21: Validar contrasena minimo 8 caracteres
    if (strlen($newPassword) < 8 || strlen($newPassword) > 128) {
        json_response(['error' => 'La nueva contrasena debe tener al menos 8 caracteres'], 422);
    }
    $full = User::findByEmailAny($user['correo']);
    if (!$full || !password_verify($currentPassword, $full['password_hash'])) {
        json_response(['error' => 'La contrasena actual es incorrecta'], 401);
    }
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $db->prepare('UPDATE usuarios SET nombre = :n, telefono = :t, password_hash = :p WHERE id_usuario = :id');
    $stmt->execute(['n' => $nombre, 't' => $telefono ?: null, 'p' => $hash, 'id' => $user['id_usuario']]);

    log_audit($user['id_usuario'], 'UPDATE_PROFILE_PASSWORD', 'usuarios', $user['id_usuario'], 'Cambio de contrasena y datos de perfil');
} else {
    $stmt = $db->prepare('UPDATE usuarios SET nombre = :n, telefono = :t WHERE id_usuario = :id');
    $stmt->execute(['n' => $nombre, 't' => $telefono ?: null, 'id' => $user['id_usuario']]);

    log_audit($user['id_usuario'], 'UPDATE_PROFILE', 'usuarios', $user['id_usuario'], 'Actualizacion de datos basicos de perfil');
}

json_response(['success' => true, 'message' => 'Perfil actualizado correctamente']);
