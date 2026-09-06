<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/audit.php';
require_once __DIR__ . '/../../models/Treatment.php';

start_session();

// Solo Administradores (SuperAdmin 2 o Recepcionista 3)
$user = require_role([2, 3]);
$userId = (int) $user['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'subcategories') {
        $subcats = Treatment::getAllSubcategories();
        json_response(['success' => true, 'subcategorias' => $subcats]);
    }

    $id = !empty($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id > 0) {
        $servicio = Treatment::findById($id);
        if (!$servicio) {
            json_response(['error' => 'Servicio no encontrado'], 404);
        }
        json_response(['success' => true, 'servicio' => $servicio]);
    }

    $servicios = Treatment::getAllAdmin();
    json_response(['success' => true, 'servicios' => $servicios]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_input();
    $token = $input['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
    }

    $action = $input['action'] ?? 'create';

    if ($action === 'create') {
        $subcatId = (int) ($input['subcategoria_id'] ?? 0);
        $nombre   = trim($input['nombre'] ?? '');
        $desc     = trim($input['descripcion'] ?? '');
        $duracion = (int) ($input['duracion_minutos'] ?? 0);
        $precio   = (float) ($input['precio'] ?? 0);
        $activo   = isset($input['activo']) ? (bool) $input['activo'] : true;

        if ($subcatId <= 0 || $nombre === '' || $duracion <= 0 || $precio < 0) {
            json_response(['error' => 'Datos invalidos para la creacion del servicio'], 422);
        }

        try {
            $newId = Treatment::create($subcatId, $nombre, $desc, $duracion, $precio, $activo);
            log_audit($userId, 'CREATE_SERVICE', 'servicios', $newId, "Servicio creado: '{$nombre}'");
            json_response([
                'success' => true,
                'message' => 'Servicio creado exitosamente',
                'id'      => $newId,
            ]);
        } catch (\Throwable $e) {
            error_log('Error creando servicio: ' . $e->getMessage());
            json_response(['error' => 'No se pudo crear el servicio'], 500);
        }
    }

    if ($action === 'update') {
        $id       = (int) ($input['id_servicio'] ?? $input['id'] ?? 0);
        $subcatId = (int) ($input['subcategoria_id'] ?? 0);
        $nombre   = trim($input['nombre'] ?? '');
        $desc     = trim($input['descripcion'] ?? '');
        $duracion = (int) ($input['duracion_minutos'] ?? 0);
        $precio   = (float) ($input['precio'] ?? 0);
        $activo   = isset($input['activo']) ? (bool) $input['activo'] : null;

        if ($id <= 0 || $subcatId <= 0 || $nombre === '' || $duracion <= 0 || $precio < 0) {
            json_response(['error' => 'Datos invalidos para la actualizacion del servicio'], 422);
        }

        try {
            $updated = Treatment::update($id, $subcatId, $nombre, $desc, $duracion, $precio, $activo);
            log_audit($userId, 'UPDATE_SERVICE', 'servicios', $id, "Servicio actualizado: '{$nombre}'");
            json_response([
                'success' => true,
                'message' => 'Servicio actualizado correctamente',
            ]);
        } catch (\Throwable $e) {
            error_log('Error actualizando servicio: ' . $e->getMessage());
            json_response(['error' => 'No se pudo actualizar el servicio'], 500);
        }
    }

    if ($action === 'toggle') {
        $id     = (int) ($input['id_servicio'] ?? $input['id'] ?? 0);
        $activo = isset($input['activo']) ? (bool) $input['activo'] : null;

        if ($id <= 0) {
            json_response(['error' => 'ID de servicio invalido'], 422);
        }

        try {
            Treatment::toggleActive($id, $activo);
            log_audit($userId, 'TOGGLE_SERVICE', 'servicios', $id, "Estado activo/inactivo alternado");
            json_response([
                'success' => true,
                'message' => 'Estado del servicio actualizado correctamente',
            ]);
        } catch (\Throwable $e) {
            error_log('Error alternando estado de servicio: ' . $e->getMessage());
            json_response(['error' => 'No se pudo cambiar el estado del servicio'], 500);
        }
    }

    json_response(['error' => 'Accion no valida'], 422);
}

json_response(['error' => 'Metodo no permitido'], 405);
