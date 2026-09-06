<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/User.php';

start_session();
require_login();

$user = current_user();
$rol = (int) ($user['id_rol'] ?? 0);

if ($rol !== 2) {
    json_response(['error' => 'Acceso exclusivo para SuperAdmin'], 403);
}

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $staff = User::getAllStaff();
    $rolesStmt = $db->query('SELECT id_rol, nombre_rol FROM roles WHERE id_rol IN (2, 3, 4) ORDER BY id_rol');
    $roles = $rolesStmt->fetchAll();

    json_response([
        'success' => true,
        'staff'   => $staff,
        'roles'   => $roles,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_input();
    $token = $input['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        json_response(['error' => 'Token de seguridad invalido'], 403);
    }

    $action = $input['action'] ?? '';

    if ($action === 'create') {
        $nombre = trim($input['nombre'] ?? '');
        $correo = trim($input['correo'] ?? '');
        $password = $input['password'] ?? '';
        $idRol = (int) ($input['id_rol'] ?? 4);

        if (empty($nombre) || empty($correo) || empty($password)) {
            json_response(['error' => 'Nombre, correo y contrasena son obligatorios'], 422);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => 'Correo electronico no valido'], 422);
        }

        if (User::findByEmailAny($correo)) {
            json_response(['error' => 'Ya existe un usuario registrado con este correo'], 409);
        }

        try {
            $newId = User::createStaff($input);
            log_audit((int)$user['id_usuario'], "CREAR_PERSONAL #{$newId} ({$nombre}, Rol: {$idRol})", 'usuarios');
            json_response(['success' => true, 'message' => 'Empleado registrado exitosamente', 'id' => $newId]);
        } catch (\PDOException $e) {
            json_response(['error' => 'Error al crear empleado: ' . $e->getMessage()], 500);
        }
    }

    if ($action === 'update') {
        $id = (int) ($input['id_usuario'] ?? 0);
        $nombre = trim($input['nombre'] ?? '');
        $correo = trim($input['correo'] ?? '');

        if ($id <= 0 || empty($nombre) || empty($correo)) {
            json_response(['error' => 'Datos invalidos para actualizar el empleado'], 422);
        }

        // Verificar si el correo ya pertenece a otro
        $existing = User::findByEmailAny($correo);
        if ($existing && (int)$existing['id_usuario'] !== $id) {
            json_response(['error' => 'El correo ya pertenece a otro usuario'], 409);
        }

        try {
            User::updateStaff($id, $input);
            log_audit((int)$user['id_usuario'], "EDITAR_PERSONAL #{$id} ({$nombre})", 'usuarios');
            json_response(['success' => true, 'message' => 'Empleado actualizado exitosamente']);
        } catch (\PDOException $e) {
            json_response(['error' => 'Error al actualizar empleado: ' . $e->getMessage()], 500);
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($input['id_usuario'] ?? 0);
        if ($id <= 0) {
            json_response(['error' => 'ID de usuario invalido'], 422);
        }
        if ($id === (int)$user['id_usuario']) {
            json_response(['error' => 'No puedes desactivar tu propia cuenta'], 400);
        }

        try {
            User::toggleStaffStatus($id);
            log_audit((int)$user['id_usuario'], "TOGGLE_ESTADO_PERSONAL #{$id}", 'usuarios');
            json_response(['success' => true, 'message' => 'Estado del empleado actualizado']);
        } catch (\PDOException $e) {
            json_response(['error' => 'Error al cambiar estado: ' . $e->getMessage()], 500);
        }
    }

    json_response(['error' => 'Accion no valida'], 400);
}

json_response(['error' => 'Metodo no permitido'], 405);
