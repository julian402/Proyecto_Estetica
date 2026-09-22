<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/audit.php';
require_once __DIR__ . '/../../models/Treatment.php';

start_session();

// Solo Administradores (SuperAdmin 2 o Recepcionista 3)
$user = require_role([2, 3]);
$userId = (int) $user['id_usuario'];

function service_image_upload(): ?string {
    if (empty($_FILES['imagen']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) return null;
    $file = $_FILES['imagen'];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('La imagen no pudo cargarse o supera 5 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime]) || !@getimagesize($file['tmp_name'])) {
        throw new RuntimeException('Selecciona una imagen JPG, PNG o WebP válida.');
    }
    $dir = __DIR__ . '/../../assets/images/services';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('No se pudo preparar la carpeta de imágenes.');
    }
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }
    return 'assets/images/services/' . $name;
}

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
    $input = $_POST ?: json_input();
    $token = $input['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
    }

    $action = $input['action'] ?? 'create';

    if ($action === 'create') {
        $subcatId = (int) ($input['subcategoria_id'] ?? $input['id_subcategoria'] ?? 0);
        $nombre   = trim($input['nombre'] ?? $input['nombre_servicio'] ?? '');
        $desc     = trim($input['descripcion'] ?? '');
        $duracion = (int) ($input['duracion_minutos'] ?? 0);
        $precio   = (float) ($input['precio'] ?? 0);
        $activo   = isset($input['activo']) ? (bool) $input['activo'] : true;

        if ($subcatId <= 0 || $nombre === '' || $duracion <= 0 || $precio < 0) {
            json_response(['error' => 'Datos invalidos para la creacion del servicio'], 422);
        }

        try {
            $imageUrl = service_image_upload();
            $newId = Treatment::create($subcatId, $nombre, $desc, $duracion, $precio, $activo, $imageUrl);
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
        $subcatId = (int) ($input['subcategoria_id'] ?? $input['id_subcategoria'] ?? 0);
        $nombre   = trim($input['nombre'] ?? $input['nombre_servicio'] ?? '');
        $desc     = trim($input['descripcion'] ?? '');
        $duracion = (int) ($input['duracion_minutos'] ?? 0);
        $precio   = (float) ($input['precio'] ?? 0);
        $activo   = isset($input['activo']) ? (bool) $input['activo'] : null;

        if ($id <= 0 || $subcatId <= 0 || $nombre === '' || $duracion <= 0 || $precio < 0) {
            json_response(['error' => 'Datos invalidos para la actualizacion del servicio'], 422);
        }

        try {
            $imageUrl = service_image_upload();
            $updated = Treatment::update($id, $subcatId, $nombre, $desc, $duracion, $precio, $activo, $imageUrl);
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
