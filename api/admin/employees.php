<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/audit.php';
require_once __DIR__ . '/../../models/User.php';

start_session();

// Solo SuperAdmin (2) o Recepcionista (3) para ver; solo SuperAdmin (2) para modificar personal
$user = current_user();
if (!$user || !in_array((int) ($user['id_rol'] ?? 0), [2, 3], true)) {
    json_response(['error' => 'No tienes permisos de administracion'], 403);
}

$userId = (int) $user['id_usuario'];
$isSuperAdmin = ((int) $user['id_rol'] === 2);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = !empty($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id > 0) {
        $employee = User::findById($id);
        if (!$employee || !in_array((int) $employee['id_rol'], [2, 3, 4], true)) {
            json_response(['error' => 'Empleado no encontrado'], 404);
        }
        json_response(['success' => true, 'empleado' => $employee]);
    }

    $employees = User::getAllEmployees();
    $rolesStmt = getDB()->query('SELECT id_rol, nombre_rol FROM roles WHERE id_rol IN (2, 3, 4) ORDER BY id_rol');
    $roles = $rolesStmt->fetchAll();

    json_response([
        'success'   => true,
        'empleados' => $employees,
        'staff'     => $employees,
        'roles'     => $roles,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Solo SuperAdmin puede crear, editar o eliminar empleados
    if (!$isSuperAdmin) {
        json_response(['error' => 'Solo el SuperAdmin puede gestionar el personal'], 403);
    }

    $input = json_input();
    $token = $input['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
    }

    $action = $input['action'] ?? 'create';

    if ($action === 'create') {
        $nombre   = trim($input['name'] ?? $input['nombre'] ?? '');
        $correo   = strtolower(trim($input['email'] ?? $input['correo'] ?? ''));
        $password = $input['password'] ?? '';
        $rolId    = (int) ($input['rol_id'] ?? $input['id_rol'] ?? 4);
        $phone    = trim($input['phone'] ?? $input['telefono'] ?? '');

        if ($nombre === '' || $correo === '' || $password === '') {
            json_response(['error' => 'Nombre, correo y contrasena son obligatorios'], 422);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => 'Correo electronico invalido'], 422);
        }

        // Tarea 21: Minimo 8 caracteres
        if (strlen($password) < 8 || strlen($password) > 128) {
            json_response(['error' => 'La contrasena debe tener entre 8 y 128 caracteres'], 422);
        }

        // Roles permitidos: Recepcionista (3), Esteticista (4)
        if (!in_array($rolId, [2, 3, 4], true)) {
            json_response(['error' => 'Rol seleccionado invalido'], 422);
        }

        try {
            $newId = User::createEmployee($nombre, $correo, $password, $rolId, $phone ?: null);
            log_audit($userId, 'CREATE_EMPLOYEE', 'usuarios', $newId, "Empleado creado: {$nombre} ({$correo}), Rol: {$rolId}");
            json_response([
                'success' => true,
                'message' => 'Empleado registrado correctamente',
                'id'      => $newId,
            ]);
        } catch (\RuntimeException $e) {
            json_response(['error' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            error_log('Error creando empleado: ' . $e->getMessage());
            json_response(['error' => 'No se pudo crear el empleado'], 500);
        }
    }

    if ($action === 'update') {
        $id       = (int) ($input['id_usuario'] ?? $input['id'] ?? 0);
        $nombre   = trim($input['name'] ?? $input['nombre'] ?? '');
        $correo   = strtolower(trim($input['email'] ?? $input['correo'] ?? ''));
        $rolId    = (int) ($input['rol_id'] ?? $input['id_rol'] ?? 4);
        $phone    = trim($input['phone'] ?? $input['telefono'] ?? '');
        $password = $input['password'] ?? null;
        $estado   = isset($input['estado_cuenta']) ? (int) $input['estado_cuenta'] : null;

        if ($id <= 0 || $nombre === '' || $correo === '') {
            json_response(['error' => 'Datos de empleado invalidos'], 422);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => 'Correo electronico invalido'], 422);
        }

        if (!empty($password) && (strlen($password) < 8 || strlen($password) > 128)) {
            json_response(['error' => 'La contrasena debe tener al menos 8 caracteres'], 422);
        }

        if (!in_array($rolId, [2, 3, 4], true)) {
            json_response(['error' => 'Rol invalido'], 422);
        }

        try {
            User::updateEmployee($id, $nombre, $correo, $rolId, $phone ?: null, $password, $estado);
            log_audit($userId, 'UPDATE_EMPLOYEE', 'usuarios', $id, "Empleado actualizado: {$nombre} ({$correo}), Rol: {$rolId}");
            json_response([
                'success' => true,
                'message' => 'Empleado actualizado correctamente',
            ]);
        } catch (\RuntimeException $e) {
            json_response(['error' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            error_log('Error actualizando empleado: ' . $e->getMessage());
            json_response(['error' => 'No se pudo actualizar el empleado'], 500);
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($input['id_usuario'] ?? $input['id'] ?? 0);

        if ($id <= 0) {
            json_response(['error' => 'ID de empleado invalido'], 422);
        }

        if ($id === $userId) {
            json_response(['error' => 'No puedes desactivar tu propia cuenta'], 422);
        }

        $empleado = User::findById($id);
        if (!$empleado || !in_array((int) $empleado['id_rol'], [2, 3, 4], true)) {
            json_response(['error' => 'Empleado no encontrado'], 404);
        }

        $nuevoEstado = ((int) $empleado['estado_cuenta'] === 1) ? 0 : 1;

        try {
            User::updateEmployee(
                $id,
                $empleado['nombre'],
                $empleado['correo'],
                (int) $empleado['id_rol'],
                !empty($empleado['telefono']) ? $empleado['telefono'] : null,
                null,
                $nuevoEstado
            );
            log_audit($userId, 'TOGGLE_EMPLOYEE', 'usuarios', $id, ($nuevoEstado ? 'Empleado activado' : 'Empleado desactivado') . ", id: {$id}");
            json_response([
                'success' => true,
                'message' => $nuevoEstado ? 'Empleado activado correctamente' : 'Empleado desactivado correctamente',
            ]);
        } catch (\RuntimeException $e) {
            json_response(['error' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            error_log('Error cambiando estado de empleado: ' . $e->getMessage());
            json_response(['error' => 'No se pudo cambiar el estado del empleado'], 500);
        }
    }

    if ($action === 'delete') {
        $id = (int) ($input['id_usuario'] ?? $input['id'] ?? 0);

        if ($id <= 0) {
            json_response(['error' => 'ID de empleado invalido'], 422);
        }

        // Prevenir auto-eliminacion
        if ($id === $userId) {
            json_response(['error' => 'No puedes eliminar tu propia cuenta de administrador'], 422);
        }

        try {
            // HU09: Bloquea eliminacion si tiene citas pendientes
            User::deleteEmployee($id);
            log_audit($userId, 'DELETE_EMPLOYEE', 'usuarios', $id, "Empleado eliminado/desactivado id: {$id}");
            json_response([
                'success' => true,
                'message' => 'Empleado procesado correctamente',
            ]);
        } catch (\DomainException $e) {
            // HU09: Conflicto por citas pendientes
            json_response(['error' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            error_log('Error eliminando empleado: ' . $e->getMessage());
            json_response(['error' => 'No se pudo eliminar el empleado'], 500);
        }
    }

    json_response(['error' => 'Accion no valida'], 422);
}

json_response(['error' => 'Metodo no permitido'], 405);
