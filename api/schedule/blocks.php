<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/audit.php';
require_once __DIR__ . '/../../models/User.php';

start_session();

// Solo Admin (2, 3) o Esteticista (4) - Tarea 5 / HU13
$user = require_role([2, 3, 4]);
$userId = (int) $user['id_usuario'];
$rol    = (int) $user['id_rol'];

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $where = [];
    $params = [];

    // Si es esteticista, forzar consulta exclusiva de sus propios bloqueos
    if ($rol === 4) {
        $where[] = 'a.id_esteticista = :est_id';
        $params['est_id'] = $userId;
    } elseif (!empty($_GET['esteticista_id'])) {
        $where[] = 'a.id_esteticista = :est_id';
        $params['est_id'] = (int) $_GET['esteticista_id'];
    }

    if (!empty($_GET['fecha'])) {
        $where[] = 'DATE(a.fecha_hora_inicio) = :fecha';
        $params['fecha'] = trim($_GET['fecha']);
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT a.id_bloqueo, a.id_esteticista, a.fecha_hora_inicio, a.fecha_hora_fin, a.motivo,
                   u.nombre AS nombre_esteticista, u.correo AS correo_esteticista
            FROM ausencias_bloqueos a
            JOIN usuarios u ON a.id_esteticista = u.id_usuario
            {$whereClause}
            ORDER BY a.fecha_hora_inicio DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $bloqueos = $stmt->fetchAll();

    json_response(['success' => true, 'bloqueos' => $bloqueos]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_input();
    $token = $input['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
    }

    $action = $input['action'] ?? 'create';

    if ($action === 'create') {
        $targetEsteticId = ($rol === 4)
            ? $userId
            : (int) ($input['esteticista_id'] ?? 0);

        $inicio = trim($input['fecha_hora_inicio'] ?? ($input['fecha_inicio'] ?? ''));
        $fin    = trim($input['fecha_hora_fin'] ?? ($input['fecha_fin'] ?? ''));
        $motivo = trim($input['motivo'] ?? 'Bloqueo de agenda');

        if ($targetEsteticId <= 0 || $inicio === '' || $fin === '') {
            json_response(['error' => 'Esteticista, fecha de inicio y fecha de fin son obligatorios'], 422);
        }

        if (strtotime($inicio) >= strtotime($fin)) {
            json_response(['error' => 'La fecha de fin debe ser posterior a la fecha de inicio'], 422);
        }

        if (strtotime($inicio) <= time()) {
            json_response(['error' => 'El bloqueo debe programarse para una fecha futura'], 422);
        }

        try {
            $stmt = $db->prepare(
                'INSERT INTO ausencias_bloqueos (id_esteticista, fecha_hora_inicio, fecha_hora_fin, motivo)
                 VALUES (:est_id, :inicio, :fin, :motivo)'
            );
            $stmt->execute([
                'est_id' => $targetEsteticId,
                'inicio' => $inicio,
                'fin'    => $fin,
                'motivo' => $motivo ?: null,
            ]);
            $newId = (int) $db->lastInsertId();

            log_audit(
                $userId,
                'CREATE_SCHEDULE_BLOCK',
                'ausencias_bloqueos',
                $newId,
                "Bloqueo para esteticista {$targetEsteticId} de {$inicio} a {$fin}. Motivo: {$motivo}"
            );

            json_response([
                'success' => true,
                'message' => 'Bloqueo de agenda registrado exitosamente',
                'id'      => $newId,
            ]);
        } catch (\PDOException $e) {
            // El trigger trg_ausencias_before_insert lanza 45000 si hay choque con reserva activa
            if ($e->getCode() === '45000') {
                json_response(['error' => $e->getMessage()], 409);
            }
            error_log('Error creando bloqueo de agenda: ' . $e->getMessage());
            json_response(['error' => 'No se pudo crear el bloqueo de agenda: ' . $e->getMessage()], 500);
        }
    }

    if ($action === 'delete') {
        $bloqueoId = (int) ($input['id_bloqueo'] ?? $input['id'] ?? 0);
        if ($bloqueoId <= 0) {
            json_response(['error' => 'ID de bloqueo invalido'], 422);
        }

        $stmtCheck = $db->prepare('SELECT id_bloqueo, id_esteticista, fecha_hora_inicio FROM ausencias_bloqueos WHERE id_bloqueo = :id');
        $stmtCheck->execute(['id' => $bloqueoId]);
        $bloqueo = $stmtCheck->fetch();

        if (!$bloqueo) {
            json_response(['error' => 'Bloqueo no encontrado'], 404);
        }

        // Si es esteticista, solo puede eliminar sus propios bloqueos
        if ($rol === 4 && (int) $bloqueo['id_esteticista'] !== $userId) {
            json_response(['error' => 'No tienes permiso para eliminar este bloqueo'], 403);
        }

        $stmtDel = $db->prepare('DELETE FROM ausencias_bloqueos WHERE id_bloqueo = :id');
        $stmtDel->execute(['id' => $bloqueoId]);

        log_audit(
            $userId,
            'DELETE_SCHEDULE_BLOCK',
            'ausencias_bloqueos',
            $bloqueoId,
            "Bloqueo eliminado id {$bloqueoId} de esteticista {$bloqueo['id_esteticista']}"
        );

        json_response([
            'success' => true,
            'message' => 'Bloqueo de agenda eliminado correctamente',
        ]);
    }

    json_response(['error' => 'Accion no valida'], 422);
}

json_response(['error' => 'Metodo no permitido'], 405);
