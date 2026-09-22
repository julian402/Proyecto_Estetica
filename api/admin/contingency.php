<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/audit.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../models/Appointment.php';
require_once __DIR__ . '/../../models/User.php';

start_session();

// Solo SuperAdmin (2) y Recepcionista (3)
$user = require_role([2, 3]);
$userId = (int) $user['id_usuario'];

$db = getDB();

/**
 * Normaliza el rango de la ausencia.
 * Acepta 'fecha_inicio'/'fecha_fin' (datetime) o el atajo 'fecha' (un dia completo).
 */
function contingency_range(array $src): array {
    $inicio = trim((string) ($src['fecha_inicio'] ?? ''));
    $fin    = trim((string) ($src['fecha_fin'] ?? ''));

    if ($inicio === '' && $fin === '') {
        $fecha = trim((string) ($src['fecha'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $inicio = $fecha . ' 00:00:00';
            $fin    = $fecha . ' 23:59:59';
        }
    }

    return [$inicio, $fin];
}

/**
 * Fecha en espanol, para que el registro de acciones se lea de corrido.
 * Ej: 'sabado 26 de septiembre de 2026'.
 */
function fecha_legible(string $datetime): string {
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }

    $dias  = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
              'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    return sprintf(
        '%s %d de %s de %d',
        $dias[(int) date('w', $ts)],
        (int) date('j', $ts),
        $meses[(int) date('n', $ts) - 1],
        (int) date('Y', $ts)
    );
}

/**
 * Citas activas (Pendiente 1 / Confirmada 2) de un especialista que solapan el rango.
 */
function contingency_affected(PDO $db, int $esteticId, string $inicio, string $fin): array {
    $stmt = $db->prepare(
        'SELECT r.id_reserva, r.fecha_hora_inicio, r.fecha_hora_fin, r.id_estado,
                s.nombre_servicio, s.duracion_minutos,
                er.nombre_estado,
                c.id_usuario AS id_cliente, c.nombre AS nombre_cliente, c.correo AS correo_cliente, c.telefono AS telefono_cliente
         FROM reservas r
         JOIN servicios s ON r.id_servicio = s.id_servicio
         JOIN estados_reserva er ON r.id_estado = er.id_estado
         JOIN usuarios c ON r.id_cliente = c.id_usuario
         WHERE r.id_esteticista = :est_id
           AND r.id_estado IN (1, 2)
           AND :inicio < r.fecha_hora_fin
           AND :fin > r.fecha_hora_inicio
         ORDER BY r.fecha_hora_inicio ASC'
    );
    $stmt->execute(['est_id' => $esteticId, 'inicio' => $inicio, 'fin' => $fin]);
    return $stmt->fetchAll();
}

/**
 * Comprueba si un especialista puede atender un horario concreto.
 * Devuelve el motivo del choque, o null si esta libre.
 *
 * Los triggers de la base ya impiden el solape, pero abortan toda la
 * transaccion con un error crudo. Validar antes permite saltar solo las citas
 * conflictivas, reasignar el resto y explicar que paso con cada una.
 */
function contingency_conflicto(PDO $db, int $estId, string $inicio, string $fin, int $excluirReserva = 0): ?string {
    // 1. Otra cita activa del mismo especialista
    $stmt = $db->prepare(
        "SELECT r.id_reserva, r.fecha_hora_inicio
         FROM reservas r
         JOIN estados_reserva e ON e.id_estado = r.id_estado
         WHERE r.id_esteticista = :est
           AND r.id_reserva <> :excluir
           AND e.nombre_estado NOT IN ('Cancelada', 'No_Show')
           AND :inicio < r.fecha_hora_fin
           AND :fin > r.fecha_hora_inicio
         LIMIT 1"
    );
    $stmt->execute(['est' => $estId, 'excluir' => $excluirReserva, 'inicio' => $inicio, 'fin' => $fin]);
    $choque = $stmt->fetch();

    if ($choque) {
        return 'ya tiene la cita #' . $choque['id_reserva'] . ' a esa misma hora';
    }

    // 2. Un bloqueo de agenda suyo (descanso, incapacidad, contingencia)
    $stmt = $db->prepare(
        'SELECT motivo FROM ausencias_bloqueos
         WHERE id_esteticista = :est
           AND :inicio < fecha_hora_fin
           AND :fin > fecha_hora_inicio
         LIMIT 1'
    );
    $stmt->execute(['est' => $estId, 'inicio' => $inicio, 'fin' => $fin]);
    $bloqueo = $stmt->fetch();

    if ($bloqueo) {
        $motivo = trim((string) $bloqueo['motivo']);
        return 'tiene la agenda bloqueada' . ($motivo !== '' ? ' (' . $motivo . ')' : '');
    }

    return null;
}

/**
 * Carga las reservas indicadas junto con los datos del cliente, para poder notificar.
 */
function contingency_load_by_ids(PDO $db, array $ids): array {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (empty($ids)) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare(
        "SELECT r.id_reserva, r.fecha_hora_inicio, r.fecha_hora_fin, r.id_estado, r.id_esteticista,
                s.nombre_servicio,
                c.nombre AS nombre_cliente, c.correo AS correo_cliente
         FROM reservas r
         JOIN servicios s ON r.id_servicio = s.id_servicio
         JOIN usuarios c ON r.id_cliente = c.id_usuario
         WHERE r.id_reserva IN ($placeholders)"
    );
    $stmt->execute($ids);
    return $stmt->fetchAll();
}

// ============================================================
// GET: consultar citas afectadas por la ausencia
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Historial de acciones de contingencia, tomado del registro de auditoria
    // para que sobreviva a recargas y refleje tambien lo que hizo otro usuario.
    if (!empty($_GET['historial'])) {
        $stmt = $db->query(
            "SELECT l.id_log, l.accion, l.detalles, l.fecha_hora, u.nombre AS autor
             FROM logs_auditoria l
             JOIN usuarios u ON l.id_usuario = u.id_usuario
             WHERE l.accion IN ('BLOCK_DAY', 'REASSIGN_APPOINTMENT', 'CANCEL_APPOINTMENT', 'APPLY_CONTINGENCY')
             ORDER BY l.fecha_hora DESC, l.id_log DESC
             LIMIT 20"
        );

        json_response(['success' => true, 'historial' => $stmt->fetchAll()]);
    }

    $esteticId = !empty($_GET['esteticista_id']) ? (int) $_GET['esteticista_id'] : 0;
    [$inicio, $fin] = contingency_range($_GET);

    if ($esteticId <= 0 || $inicio === '' || $fin === '') {
        json_response(['error' => 'Debes indicar el especialista y la fecha de la ausencia'], 422);
    }

    $afectadas = contingency_affected($db, $esteticId, $inicio, $fin);

    $otrosEsteticistas = array_values(array_filter(User::getEsteticistas(), function ($e) use ($esteticId) {
        return (int) $e['id_usuario'] !== $esteticId;
    }));

    json_response([
        'success'            => true,
        'total_afectadas'    => count($afectadas),
        'total'              => count($afectadas),
        'citas_afectadas'    => $afectadas,
        'reservas'           => $afectadas,
        'otros_esteticistas' => $otrosEsteticistas,
        'esteticistas'       => $otrosEsteticistas,
    ]);
}

// ============================================================
// POST: aplicar acciones de contingencia
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_input();
    $token = $input['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
    }

    $action = $input['action'] ?? 'apply';

    // --------------------------------------------------------
    // Reasignar un lote de citas a otro especialista
    // --------------------------------------------------------
    if ($action === 'reassign_bulk') {
        $ids      = is_array($input['reserva_ids'] ?? null) ? $input['reserva_ids'] : [];
        $newEstId = (int) ($input['nuevo_esteticista_id'] ?? $input['id_esteticista'] ?? 0);

        if (empty($ids)) {
            json_response(['error' => 'Selecciona al menos una cita para reasignar'], 422);
        }

        $nuevo = User::findById($newEstId);
        if (!$nuevo || (int) $nuevo['id_rol'] !== 4 || empty($nuevo['estado_cuenta'])) {
            json_response(['error' => 'El especialista destino no es valido'], 422);
        }

        $reservas = contingency_load_by_ids($db, $ids);
        if (empty($reservas)) {
            json_response(['error' => 'No se encontraron las citas seleccionadas'], 404);
        }

        $motivo    = trim($input['motivo'] ?? 'Reasignacion por ausencia imprevista del especialista');
        $notificar = [];
        $avisos    = [];

        $db->beginTransaction();
        try {
            foreach ($reservas as $cita) {
                $rId = (int) $cita['id_reserva'];

                if ((int) $cita['id_esteticista'] === $newEstId) {
                    $avisos[] = "La cita #{$rId} ya estaba asignada a {$nuevo['nombre']}";
                    continue;
                }

                // El destino debe tener ese horario libre: ni otra cita ni un bloqueo.
                $conflicto = contingency_conflicto(
                    $db,
                    $newEstId,
                    $cita['fecha_hora_inicio'],
                    $cita['fecha_hora_fin'],
                    $rId
                );

                if ($conflicto !== null) {
                    $avisos[] = "La cita #{$rId} no se reasignó: {$nuevo['nombre']} {$conflicto}";
                    continue;
                }

                $db->prepare('SET @app_usuario_modifica = ?, @app_motivo = ?')->execute([$userId, $motivo]);

                $stmtUpd = $db->prepare('UPDATE reservas SET id_esteticista = :new_est WHERE id_reserva = :id');
                $stmtUpd->execute(['new_est' => $newEstId, 'id' => $rId]);

                log_audit($userId, 'REASSIGN_APPOINTMENT', 'reservas', $rId, "Cita #{$rId} de {$cita['nombre_cliente']} reasignada a {$nuevo['nombre']} — " . fecha_legible($cita['fecha_hora_inicio']));

                $notificar[] = $cita;
            }

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error reasignando citas por contingencia: ' . $e->getMessage());
            json_response(['error' => 'No se pudieron reasignar las citas: ' . $e->getMessage()], 500);
        }

        // Correos fuera de la transaccion: el cambio ya esta confirmado en BD
        foreach ($notificar as $cita) {
            try {
                send_contingency_notification(
                    [
                        'id_reserva'      => $cita['id_reserva'],
                        'nombre_servicio' => $cita['nombre_servicio'],
                        'fecha_inicio'    => $cita['fecha_hora_inicio'],
                    ],
                    ['nombre' => $cita['nombre_cliente'], 'correo' => $cita['correo_cliente']],
                    $nuevo['nombre']
                );
            } catch (\Throwable $e) {
                error_log('Error despachando correo de reasignacion: ' . $e->getMessage());
            }
        }

        $mensaje = count($notificar) === 0
            ? 'No se reasignó ninguna cita: ' . $nuevo['nombre'] . ' no tiene libres esos horarios.'
            : count($notificar) . ' cita(s) reasignada(s) a ' . $nuevo['nombre'] . '. Se notificó por correo a los clientes.';

        if (!empty($avisos)) {
            $mensaje .= ' ' . count($avisos) . ' se omitió(eron) por conflicto de agenda.';
        }

        json_response([
            'success'     => true,
            'message'     => $mensaje,
            'reasignadas' => count($notificar),
            'omitidas'    => count($avisos),
            'avisos'      => $avisos,
        ]);
    }

    // --------------------------------------------------------
    // Cancelar un lote de citas
    // --------------------------------------------------------
    if ($action === 'cancel_bulk') {
        $ids = is_array($input['reserva_ids'] ?? null) ? $input['reserva_ids'] : [];

        if (empty($ids)) {
            json_response(['error' => 'Selecciona al menos una cita para cancelar'], 422);
        }

        $reservas = contingency_load_by_ids($db, $ids);
        if (empty($reservas)) {
            json_response(['error' => 'No se encontraron las citas seleccionadas'], 404);
        }

        $motivo     = trim($input['motivo'] ?? 'Cancelacion por ausencia imprevista del especialista');
        $canceladas = [];

        $db->beginTransaction();
        try {
            foreach ($reservas as $cita) {
                $rId = (int) $cita['id_reserva'];

                // No se tocan las completadas ni las ya canceladas
                if (in_array((int) $cita['id_estado'], [3, 4], true)) {
                    continue;
                }

                $db->prepare('SET @app_usuario_modifica = ?, @app_motivo = ?')->execute([$userId, $motivo]);
                $db->prepare('UPDATE reservas SET id_estado = 4 WHERE id_reserva = :id')->execute(['id' => $rId]);

                log_audit($userId, 'CANCEL_APPOINTMENT', 'reservas', $rId, "Cita #{$rId} de {$cita['nombre_cliente']} cancelada por contingencia — " . fecha_legible($cita['fecha_hora_inicio']));

                $canceladas[] = $cita;
            }

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error cancelando citas por contingencia: ' . $e->getMessage());
            json_response(['error' => 'No se pudieron cancelar las citas: ' . $e->getMessage()], 500);
        }

        foreach ($canceladas as $cita) {
            try {
                send_status_change_notification(
                    [
                        'id_reserva'      => $cita['id_reserva'],
                        'nombre_servicio' => $cita['nombre_servicio'],
                        'fecha_inicio'    => $cita['fecha_hora_inicio'],
                    ],
                    ['nombre' => $cita['nombre_cliente'], 'correo' => $cita['correo_cliente']],
                    (int) $cita['id_estado'],
                    4
                );
            } catch (\Throwable $e) {
                error_log('Error despachando correo de cancelacion por contingencia: ' . $e->getMessage());
            }
        }

        json_response([
            'success'    => true,
            'message'    => count($canceladas) . ' cita(s) cancelada(s). Se notifico por correo a los clientes.',
            'canceladas' => count($canceladas),
        ]);
    }

    // --------------------------------------------------------
    // Bloquear el dia completo de un especialista
    // --------------------------------------------------------
    if ($action === 'block_day') {
        $esteticId = (int) ($input['id_esteticista'] ?? $input['esteticista_id'] ?? 0);
        [$inicio, $fin] = contingency_range($input);
        $motivo = trim($input['motivo'] ?? 'Ausencia imprevista del especialista');

        if ($esteticId <= 0 || $inicio === '' || $fin === '') {
            json_response(['error' => 'Debes indicar el especialista y la fecha a bloquear'], 422);
        }

        $especialista = User::findById($esteticId);
        if (!$especialista || (int) $especialista['id_rol'] !== 4) {
            json_response(['error' => 'El especialista seleccionado no existe o no es valido'], 404);
        }

        try {
            // Bypass del trigger: la contingencia si puede solapar citas existentes
            $db->exec('SET @skip_ausencias_trigger = 1');

            $stmt = $db->prepare(
                'INSERT INTO ausencias_bloqueos (id_esteticista, fecha_hora_inicio, fecha_hora_fin, motivo)
                 VALUES (:est_id, :inicio, :fin, :motivo)'
            );
            $stmt->execute([
                'est_id' => $esteticId,
                'inicio' => $inicio,
                'fin'    => $fin,
                'motivo' => $motivo,
            ]);
            $bloqueoId = (int) $db->lastInsertId();

            $db->exec('SET @skip_ausencias_trigger = 0');

            log_audit($userId, 'BLOCK_DAY', 'ausencias_bloqueos', $bloqueoId, "Día completo bloqueado a {$especialista['nombre']} — " . fecha_legible($inicio));

            json_response([
                'success'    => true,
                'message'    => 'Dia bloqueado correctamente para ' . $especialista['nombre'],
                'bloqueo_id' => $bloqueoId,
            ]);
        } catch (\Throwable $e) {
            $db->exec('SET @skip_ausencias_trigger = 0');
            error_log('Error bloqueando el dia por contingencia: ' . $e->getMessage());
            json_response(['error' => 'No se pudo bloquear el dia: ' . $e->getMessage()], 500);
        }
    }

    // --------------------------------------------------------
    // Plan de contingencia completo: bloqueo del rango + reasignacion masiva
    // --------------------------------------------------------
    $esteticId = (int) ($input['esteticista_id'] ?? $input['id_esteticista'] ?? 0);
    [$inicio, $fin] = contingency_range($input);
    $motivo    = trim($input['motivo'] ?? 'Ausencia inesperada de fuerza mayor');
    $reassigns = $input['reasignaciones'] ?? []; // mapa [reserva_id => nuevo_esteticista_id]

    if ($esteticId <= 0 || $inicio === '' || $fin === '') {
        json_response(['error' => 'Debes indicar el especialista y la fecha de la ausencia'], 422);
    }

    $especialista = User::findById($esteticId);
    if (!$especialista || (int) $especialista['id_rol'] !== 4) {
        json_response(['error' => 'El esteticista seleccionado no existe o no es valido'], 404);
    }

    $afectadas = contingency_affected($db, $esteticId, $inicio, $fin);
    $notificar = [];
    $avisos    = [];
    $bloqueoId = 0;

    $db->beginTransaction();
    try {
        $db->exec('SET @skip_ausencias_trigger = 1');

        $stmtBloqueo = $db->prepare(
            'INSERT INTO ausencias_bloqueos (id_esteticista, fecha_hora_inicio, fecha_hora_fin, motivo)
             VALUES (:est_id, :inicio, :fin, :motivo)'
        );
        $stmtBloqueo->execute([
            'est_id' => $esteticId,
            'inicio' => $inicio,
            'fin'    => $fin,
            'motivo' => $motivo,
        ]);
        $bloqueoId = (int) $db->lastInsertId();

        foreach ($afectadas as $cita) {
            $rId = (int) $cita['id_reserva'];
            $newEstId = !empty($reassigns[$rId]) ? (int) $reassigns[$rId] : null;

            $motivoHist = "Contingencia por ausencia imprevista de {$especialista['nombre']}: {$motivo}";
            $db->prepare('SET @app_usuario_modifica = ?, @app_motivo = ?')->execute([$userId, $motivoHist]);

            // Solo se traspasa si el destino tiene ese horario libre. Si no,
            // la cita queda marcada como Reasignada sin especialista nuevo.
            $conflicto = $newEstId
                ? contingency_conflicto($db, $newEstId, $cita['fecha_hora_inicio'], $cita['fecha_hora_fin'], $rId)
                : null;

            if ($conflicto !== null) {
                $avisos[] = "La cita #{$rId} quedó sin especialista: el destino {$conflicto}";
                $newEstId = null;
            }

            if ($newEstId && $newEstId > 0 && $newEstId !== $esteticId) {
                $stmtUpd = $db->prepare(
                    'UPDATE reservas SET id_esteticista = :new_est, id_estado = 5 WHERE id_reserva = :id'
                );
                $stmtUpd->execute(['new_est' => $newEstId, 'id' => $rId]);
            } else {
                // Reasignada (5): pendiente de reprogramar o de nuevo especialista
                $db->prepare('UPDATE reservas SET id_estado = 5 WHERE id_reserva = :id')->execute(['id' => $rId]);
            }

            $notificar[] = $cita;
        }

        $db->exec('SET @skip_ausencias_trigger = 0');

        log_audit(
            $userId,
            'APPLY_CONTINGENCY',
            'ausencias_bloqueos',
            $bloqueoId,
            "Plan de contingencia aplicado a {$especialista['nombre']} — " . fecha_legible($inicio) . '. Citas afectadas: ' . count($afectadas)
        );

        $db->commit();
    } catch (\Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Error aplicando contingencia: ' . $e->getMessage());
        json_response(['error' => 'Error al aplicar el plan de contingencia: ' . $e->getMessage()], 500);
    }

    // Correos fuera de la transaccion: si el envio falla, la operacion ya quedo confirmada
    foreach ($notificar as $cita) {
        try {
            send_contingency_notification(
                [
                    'id_reserva'      => $cita['id_reserva'],
                    'nombre_servicio' => $cita['nombre_servicio'],
                    'fecha_inicio'    => $cita['fecha_hora_inicio'],
                ],
                ['nombre' => $cita['nombre_cliente'], 'correo' => $cita['correo_cliente']],
                $especialista['nombre']
            );
        } catch (\Throwable $e) {
            error_log('Error despachando correo de contingencia: ' . $e->getMessage());
        }
    }

    $mensaje = 'Plan de contingencia activado. Se notificó por correo a los clientes afectados.';
    if (!empty($avisos)) {
        $mensaje .= ' ' . count($avisos) . ' cita(s) quedaron sin especialista por conflicto de agenda.';
    }

    json_response([
        'success'         => true,
        'message'         => $mensaje,
        'citas_afectadas' => count($notificar),
        'avisos'          => $avisos,
        'bloqueo_id'      => $bloqueoId,
    ]);
}

json_response(['error' => 'Metodo no permitido'], 405);
