<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/User.php';

start_session();
require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_response([
        'success' => true,
        'user'    => [
            'name'  => $user['nombre'],
            'email' => $user['correo'],
            'phone' => $user['telefono'] ?? '',
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
$telefono = trim($input['phone'] ?? '');

if ($nombre === '') {
    json_response(['error' => 'El nombre es obligatorio'], 422);
}

$db = getDB();

$currentPassword = $input['current_password'] ?? '';
$newPassword     = $input['new_password'] ?? '';

if ($newPassword !== '') {
    if (strlen($newPassword) < 6) {
        json_response(['error' => 'La nueva contrasena debe tener al menos 6 caracteres'], 422);
    }
    $full = User::findByEmail($user['correo']);
    if (!$full || !password_verify($currentPassword, $full['password_hash'])) {
        json_response(['error' => 'La contrasena actual es incorrecta'], 401);
    }
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $db->prepare('UPDATE usuarios SET nombre = :n, telefono = :t, password_hash = :p WHERE id_usuario = :id');
    $stmt->execute(['n' => $nombre, 't' => $telefono ?: null, 'p' => $hash, 'id' => $user['id_usuario']]);
} else {
    $stmt = $db->prepare('UPDATE usuarios SET nombre = :n, telefono = :t WHERE id_usuario = :id');
    $stmt->execute(['n' => $nombre, 't' => $telefono ?: null, 'id' => $user['id_usuario']]);
}

json_response(['success' => true, 'message' => 'Perfil actualizado correctamente']);
