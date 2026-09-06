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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $esteticId = !empty($_GET['esteticista_id']) ? (int) $_GET['esteticista_id'] : 0;
    $inicio    = trim($_GET['fecha_inicio'] ?? '');
    $fin       = trim($_GET['fecha_fin'] ?? '');

    if ($esteticId <= 0 || $inicio === '' || $fin === '') {
        json_response(['error' => 'Parametros requeridos: esteticista_id, fecha_inicio, fecha_fin'], 422);
    }

    $stmt = $db->prepare(
        'SELECT r.id_reserva, r.fecha_hora_inicio, r.fecha_hora_fin,
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
    $stmt->execute([
        'est_id' => $esteticId,
        'inicio' => $inicio,
        'fin'    => $fin,
    ]);
    $afectadas = $stmt->fetchAll();

    $otrosEsteticistas = array_filter(User::getEsteticistas(), function ($e) use ($esteticId) {
        return (int) $e['id_usuario'] !== $esteticId;
    });

    json_response([
        'success'           => true,
        'total_afectadas'   => count($afectadas),
        'citas_afectadas'   => $afectadas,
        'otros_esteticistas'=> array_values($otrosEsteticistas),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_input();
    $token = $input['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        json_response(['error' => 'Token de seguridad invalido. Recarga la pagina.'], 403);
    }

    $esteticId = (int) ($input['esteticista_id'] ?? 0);
    $inicio    = trim($input['fecha_inicio'] ?? '');
    $fin       = trim($input['fecha_fin'] ?? '');
    $motivo    = trim($input['motivo'] ?? 'Ausencia inesperada de fuerza mayor');
    $reassigns = $input['reasignaciones'] ?? []; // Map o list de [reserva_id => new_esteticista_id]

    if ($esteticId <= 0 || $inicio === '' || $fin === '') {
        json_response(['error' => 'Parametros requeridos: esteticista_id, fecha_inicio, fecha_fin'], 422);
    }

    $especialista = User::findById($esteticId);
    if (!$especialista || (int) $especialista['id_rol'] !== 4) {
        json_response(['error' => 'El esteticista seleccionado no existe o no es valido'], 404);
    }

    // 1. Obtener citas afectadas
    $stmt = $db->prepare(
        'SELECT r.id_reserva, r.fecha_hora_inicio, r.fecha_hora_fin, r.id_estado,
                s.nombre_servicio,
                c.id_usuario AS id_cliente, c.nombre AS nombre_cliente, c.correo AS correo_cliente, c.telefono AS telefono_cliente
         FROM reservas r
         JOIN servicios s ON r.id_servicio = s.id_servicio
         JOIN usuarios c ON r.id_cliente = c.id_usuario
         WHERE r.id_esteticista = :est_id
           AND r.id_estado IN (1, 2)
           AND :inicio < r.fecha_hora_fin
           AND :fin > r.fecha_hora_inicio'
    );
    $stmt->execute([
        'est_id' => $esteticId,
        'inicio' => $inicio,
        'fin'    => $fin,
    ]);
    $afectadas = $stmt->fetchAll();

    $db->beginTransaction();
    try {
        // Habilitar bypass del trigger para registrar la contingencia en ausencias_bloqueos
        $db->exec('SET @skip_ausencias_trigger = 1');

        $stmtBloqueo = $db->prepare(
            'INSERT INTO ausencias_bloqueos (id_esteticista, fecha_hora_inicio, fecha_hora_fin, motivo)
             VALUES (:est_id, :inicio, :fin, :motivo)'
        );
        $stmtBloqueo->execute([
            'est_id' => $esteticId,
            'inicio' => $inicio,
            'fin'    => $fin,
            'motivo' => "[Contingencia HU14] {$motivo}",
        ]);
        $bloqueoId = (int) $db->lastInsertId();

        // 2. Procesar cada cita afectada
        foreach ($afectadas as $cita) {
            $rId = (int) $cita['id_reserva'];
            $newEstId = !empty($reassigns[$rId]) ? (int) $reassigns[$rId] : null;

            $motivoHist = "Contingencia por ausencia imprevista de {$especialista['nombre']}: {$motivo}";

            $db->prepare('SET @app_usuario_modifica = ?, @app_motivo = ?')->execute([$userId, $motivoHist]);

            if ($newEstId && $newEstId > 0 && $newEstId !== $esteticId) {
                // Reasignar profesional
                $stmtUpd = $db->prepare(
                    'UPDATE reservas
                     SET id_esteticista = :new_est, id_estado = 5
                     WHERE id_reserva = :id'
                );
                $stmtUpd->execute(['new_est' => $newEstId, 'id' => $rId]);
            } else {
                // Marcar como Reasignada (5) pendiente de reprogramar o nuevo especialista
                $stmtUpd = $db->prepare('UPDATE reservas SET id_estado = 5 WHERE id_reserva = :id');
                $stmtUpd->execute(['id' => $rId]);
            }

            // Despachar correo de contingencia HU14
            try {
                $appointmentData = [
                    'id_reserva'      => $cita['id_reserva'],
                    'nombre_servicio' => $cita['nombre_servicio'],
                    'fecha_inicio'    => $cita['fecha_hora_inicio'],
                ];
                $clientData = [
                    'nombre' => $cita['nombre_cliente'],
                    'correo' => $cita['correo_cliente'],
                ];
                send_contingency_notification($appointmentData, $clientData, $especialista['nombre']);
            } catch (\Throwable $e) {
                error_log('Error despachando correo de contingencia: ' . $e->getMessage());
            }
        }

        // Restablecer variable
        $db->exec('SET @skip_ausencias_trigger = 0');

        log_audit(
            $userId,
            'APPLY_CONTINGENCY',
            'ausencias_bloqueos',
            $bloqueoId,
            "Contingencia aplicada a especialista {$especialista['nombre']}. {$inicio} a {$fin}. Citas notificadas: " . count($afectadas)
        );

        $db->commit();

        json_response([
            'success'          => true,
            'message'          => 'Plan de contingencia activado. Se notifico por correo a los clientes afectados.',
            'citas_afectadas'  => count($afectadas),
            'bloqueo_id'       => $bloqueoId,
        ]);
    } catch (\Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Error aplicando contingencia: ' . $e->getMessage());
        json_response(['error' => 'Error al aplicar el plan de contingencia: ' . $e->getMessage()], 500);
    }
}

json_response(['error' => 'Metodo no permitido'], 405);
