<?php
require_once __DIR__ . '/../../includes/auth.php';

start_session();
require_login();

$user = current_user();
$rol = (int) ($user['id_rol'] ?? 0);

if (!in_array($rol, [2, 3, 4])) {
    json_response(['error' => 'No tienes permiso para gestionar bloqueos'], 403);
}

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $esteticId = !empty($_GET['esteticista_id']) ? (int)$_GET['esteticista_id'] : null;
    if ($rol === 4) {
        $esteticId = (int)$user['id_usuario'];
    }

    $where = [];
    $params = [];
    if ($esteticId) {
        $where[] = 'b.id_esteticista = :esteticista';
        $params['esteticista'] = $esteticId;
    }

    $sql = 'SELECT b.id_bloqueo, b.id_esteticista, b.fecha_hora_inicio, b.fecha_hora_fin, b.motivo,
                   u.nombre AS nombre_esteticista
            FROM ausencias_bloqueos b
            JOIN usuarios u ON b.id_esteticista = u.id_usuario';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY b.fecha_hora_inicio DESC LIMIT 50';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    json_response(['success' => true, 'bloqueos' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_input();
    $token = $input['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        json_response(['error' => 'Token de seguridad invalido'], 403);
    }

    $action = $input['action'] ?? 'create';

    if ($action === 'delete') {
        $blockId = (int) ($input['id_bloqueo'] ?? 0);
        if ($blockId <= 0) {
            json_response(['error' => 'ID de bloqueo invalido'], 422);
        }
        $sqlCheck = 'SELECT id_esteticista FROM ausencias_bloqueos WHERE id_bloqueo = :id';
        $stmtC = $db->prepare($sqlCheck);
        $stmtC->execute(['id' => $blockId]);
        $blk = $stmtC->fetch();
        if (!$blk) {
            json_response(['error' => 'Bloqueo no encontrado'], 404);
        }
        if ($rol === 4 && (int)$blk['id_esteticista'] !== (int)$user['id_usuario']) {
            json_response(['error' => 'No puedes eliminar este bloqueo'], 403);
        }

        $stmtDel = $db->prepare('DELETE FROM ausencias_bloqueos WHERE id_bloqueo = :id');
        $stmtDel->execute(['id' => $blockId]);
        log_audit((int)$user['id_usuario'], "ELIMINAR_BLOQUEO #{$blockId}", 'ausencias_bloqueos');
        json_response(['success' => true, 'message' => 'Bloqueo eliminado correctamente']);
    }

    // Creación de bloqueo
    $esteticId = !empty($input['id_esteticista']) ? (int)$input['id_esteticista'] : (int)($input['esteticista_id'] ?? 0);
    if ($rol === 4) {
        $esteticId = (int)$user['id_usuario'];
    }

    $fecha      = trim($input['fecha'] ?? '');
    $horaInicio = trim($input['hora_inicio'] ?? '');
    $horaFin    = trim($input['hora_fin'] ?? '');
    $motivo     = trim($input['motivo'] ?? 'Break / Almuerzo');

    if ($esteticId <= 0 || empty($fecha) || empty($horaInicio) || empty($horaFin)) {
        json_response(['error' => 'Todos los campos son obligatorios'], 422);
    }

    $dtInicio = date_create_from_format('Y-m-d H:i', "{$fecha} {$horaInicio}");
    $dtFin    = date_create_from_format('Y-m-d H:i', "{$fecha} {$horaFin}");

    if (!$dtInicio || !$dtFin) {
        // Probar formato g:i a
        $dtInicio = date_create_from_format('Y-m-d g:i a', "{$fecha} " . strtolower($horaInicio));
        $dtFin    = date_create_from_format('Y-m-d g:i a', "{$fecha} " . strtolower($horaFin));
    }

    if (!$dtInicio || !$dtFin) {
        json_response(['error' => 'Formato de fecha u hora invalido'], 422);
    }

    if ($dtFin <= $dtInicio) {
        json_response(['error' => 'La hora de fin debe ser posterior a la hora de inicio'], 422);
    }

    $inicioStr = $dtInicio->format('Y-m-d H:i:s');
    $finStr    = $dtFin->format('Y-m-d H:i:s');

    // No tiene sentido bloquear un horario que ya paso
    if ($dtFin <= new DateTime('now')) {
        json_response(['error' => 'No puedes bloquear un horario que ya pasó'], 422);
    }

    // El especialista debe existir y estar activo
    require_once __DIR__ . '/../../models/User.php';
    $especialista = User::findById($esteticId);
    if (!$especialista || (int) $especialista['id_rol'] !== 4) {
        json_response(['error' => 'El especialista seleccionado no es válido'], 422);
    }
    if (empty($especialista['estado_cuenta'])) {
        json_response(['error' => 'El especialista está inactivo'], 422);
    }

    // Evitar bloqueos solapados: confunden la agenda y no aportan nada
    $stmtSolape = $db->prepare(
        'SELECT id_bloqueo, fecha_hora_inicio, fecha_hora_fin, motivo
         FROM ausencias_bloqueos
         WHERE id_esteticista = :est
           AND :inicio < fecha_hora_fin
           AND :fin > fecha_hora_inicio
         LIMIT 1'
    );
    $stmtSolape->execute(['est' => $esteticId, 'inicio' => $inicioStr, 'fin' => $finStr]);
    $solape = $stmtSolape->fetch();

    if ($solape) {
        $desde = substr($solape['fecha_hora_inicio'], 11, 5);
        $hasta = substr($solape['fecha_hora_fin'], 11, 5);
        json_response([
            'error' => "Ya existe un bloqueo de {$desde} a {$hasta} para este especialista",
        ], 409);
    }

    try {
        $stmtIns = $db->prepare(
            'INSERT INTO ausencias_bloqueos (id_esteticista, fecha_hora_inicio, fecha_hora_fin, motivo)
             VALUES (:estetic, :inicio, :fin, :motivo)'
        );
        $stmtIns->execute([
            'estetic' => $esteticId,
            'inicio'  => $inicioStr,
            'fin'     => $finStr,
            'motivo'  => $motivo,
        ]);
        $newId = (int) $db->lastInsertId();
        log_audit((int)$user['id_usuario'], "CREAR_BLOQUEO #{$newId} ({$motivo})", 'ausencias_bloqueos');
        json_response([
            'success' => true,
            'message' => 'Horario bloqueado con exito',
            'id_bloqueo' => $newId
        ]);
    } catch (\PDOException $e) {
        // El trigger impide bloquear sobre una cita activa. Su mensaje viene
        // envuelto en jerga de SQL ('SQLSTATE[45000]: ... 1644 ...'), asi que
        // se extrae solo la parte legible.
        if ($e->getCode() === '45000') {
            // Formato del driver: 'SQLSTATE[45000]: <<Unknown error>>: 1644 <mensaje>'
            $limpio = preg_replace('/^SQLSTATE\[\d+\]:.*?:\s*\d+\s+/s', '', $e->getMessage());
            json_response(['error' => trim($limpio) ?: 'No se puede bloquear: el especialista tiene citas en ese horario'], 409);
        }
        error_log('Error registrando bloqueo: ' . $e->getMessage());
        json_response(['error' => 'No se pudo registrar el bloqueo'], 500);
    }
}

json_response(['error' => 'Metodo no permitido'], 405);
