<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';

class Appointment {
    /**
     * Busca una reserva por su ID con todos los detalles relacionados.
     */
    public static function findById(int $id): ?array {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT r.id_reserva, r.id_cliente, r.id_esteticista, r.id_servicio, r.id_estado,
                    r.fecha_hora_inicio, r.fecha_hora_fin, r.creado_en,
                    s.nombre_servicio, s.duracion_minutos, s.precio,
                    er.nombre_estado,
                    est.nombre AS nombre_esteticista, est.correo AS correo_esteticista, est.telefono AS telefono_esteticista,
                    cli.nombre AS nombre_cliente, cli.correo AS correo_cliente, cli.telefono AS telefono_cliente
             FROM reservas r
             JOIN servicios s ON r.id_servicio = s.id_servicio
             JOIN estados_reserva er ON r.id_estado = er.id_estado
             JOIN usuarios est ON r.id_esteticista = est.id_usuario
             JOIN usuarios cli ON r.id_cliente = cli.id_usuario
             WHERE r.id_reserva = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Verifica si el cliente ya tiene una cita activa que se traslape con el rango indicado.
     * Tarea 9: Evita doble agendamiento por parte del cliente.
     */
    public static function hasClientConflict(int $clienteId, string $start, string $end, ?int $excludeReservaId = null): bool {
        $db = getDB();
        $sql = 'SELECT COUNT(*) FROM reservas
                WHERE id_cliente = :cid
                  AND id_estado IN (1, 2)
                  AND (:start < fecha_hora_fin AND :end > fecha_hora_inicio)';
        $params = [
            'cid'   => $clienteId,
            'start' => $start,
            'end'   => $end,
        ];

        if ($excludeReservaId !== null && $excludeReservaId > 0) {
            $sql .= ' AND id_reserva <> :exclude_id';
            $params['exclude_id'] = $excludeReservaId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    /**
     * Crea una nueva reserva.
     * $fechaInicio y $fechaFin deben estar en formato 'Y-m-d H:i:s'.
     */
    public static function create(int $clienteId, int $esteticId, int $servicioId, string $fechaInicio, string $fechaFin): int {
        // Validar conflicto de cliente previo a insercion
        if (self::hasClientConflict($clienteId, $fechaInicio, $fechaFin)) {
            throw new \DomainException('Ya tienes un servicio reservado en ese mismo horario. Por favor selecciona otra hora.');
        }

        $db = getDB();
        $stmt = $db->prepare(
            'INSERT INTO reservas (id_cliente, id_esteticista, id_servicio, id_estado, fecha_hora_inicio, fecha_hora_fin)
             VALUES (:id_cliente, :id_esteticista, :id_servicio, :id_estado, :fecha_hora_inicio, :fecha_hora_fin)'
        );
        $stmt->execute([
            'id_cliente'        => $clienteId,
            'id_esteticista'    => $esteticId,
            'id_servicio'       => $servicioId,
            'id_estado'         => 1, // Pendiente
            'fecha_hora_inicio' => $fechaInicio,
            'fecha_hora_fin'    => $fechaFin,
        ]);
        $reservaId = (int) $db->lastInsertId();

        // Registrar estado inicial en el historial
        try {
            $stmtHist = $db->prepare(
                'INSERT INTO historial_estados (id_reserva, id_estado_anterior, id_estado_nuevo, id_usuario_modifica, motivo, fecha_cambio)
                 VALUES (:reserva_id, NULL, 1, :user_id, :motivo, NOW())'
            );
            $stmtHist->execute([
                'reserva_id' => $reservaId,
                'user_id'    => $clienteId,
                'motivo'     => 'Creacion inicial de reserva',
            ]);
        } catch (\Throwable $e) {
            error_log('Error registrando historial inicial: ' . $e->getMessage());
        }

        return $reservaId;
    }

    /**
     * Retorna las reservas de un cliente con datos del servicio y esteticista.
     */
    public static function getByUser(int $userId): array {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT r.id_reserva, r.id_servicio, r.id_esteticista, r.fecha_hora_inicio, r.fecha_hora_fin, r.creado_en,
                    s.nombre_servicio, s.duracion_minutos, s.precio,
                    er.nombre_estado,
                    est.nombre AS nombre_esteticista
             FROM reservas r
             JOIN servicios s ON r.id_servicio = s.id_servicio
             JOIN estados_reserva er ON r.id_estado = er.id_estado
             JOIN usuarios est ON r.id_esteticista = est.id_usuario
             WHERE r.id_cliente = :user_id
             ORDER BY r.fecha_hora_inicio DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Cancela una reserva con registro en historial y auditoria.
     */
    public static function cancel(int $id, int $userId, ?string $motivo = null): bool {
        $reserva = self::findById($id);
        if (!$reserva) {
            return false;
        }

        // Estado 4 = Cancelada, Estado 3 = Completada
        if ((int) $reserva['id_estado'] === 4) {
            throw new \DomainException('La reserva ya se encuentra cancelada.');
        }
        if ((int) $reserva['id_estado'] === 3) {
            throw new \DomainException('No se puede cancelar una cita que ya ha sido completada.');
        }

        $db = getDB();
        $motivoTexto = $motivo ?: 'Cancelacion de cita';

        // Configurar variables de sesion para el trigger MySQL
        $db->prepare('SET @app_usuario_modifica = ?, @app_motivo = ?')->execute([$userId, $motivoTexto]);

        $stmt = $db->prepare('UPDATE reservas SET id_estado = 4 WHERE id_reserva = :id');
        $ok = $stmt->execute(['id' => $id]);

        if ($ok && $stmt->rowCount() > 0) {
            log_audit($userId, 'CANCEL_APPOINTMENT', 'reservas', $id, "Cita cancelada. Motivo: {$motivoTexto}");
            return true;
        }

        return false;
    }

    /**
     * Retorna todas las reservas con soporte de filtros: fecha, estado, esteticista y servicio.
     * Tarea 18: Filtro por servicio.
     */
    private static function adminFilters(?string $filterDate, ?int $filterEstado, ?int $filterEsteticista, ?int $filterServicio, ?string $filterCliente): array {
        $where = [];
        $params = [];

        if (!empty($filterDate)) {
            $where[] = 'DATE(r.fecha_hora_inicio) = :fecha';
            $params['fecha'] = $filterDate;
        }
        if (!empty($filterEstado)) {
            $where[] = 'r.id_estado = :id_estado';
            $params['id_estado'] = $filterEstado;
        }
        if (!empty($filterEsteticista)) {
            $where[] = 'r.id_esteticista = :id_esteticista';
            $params['id_esteticista'] = $filterEsteticista;
        }
        if (!empty($filterServicio)) {
            $where[] = 'r.id_servicio = :id_servicio';
            $params['id_servicio'] = $filterServicio;
        }
        if ($filterCliente !== null && trim($filterCliente) !== '') {
            $where[] = '(cli.nombre LIKE :cliente_nombre OR cli.correo LIKE :cliente_correo OR cli.telefono LIKE :cliente_telefono)';
            $search = '%' . trim($filterCliente) . '%';
            $params['cliente_nombre'] = $search;
            $params['cliente_correo'] = $search;
            $params['cliente_telefono'] = $search;
        }

        return [$where, $params];
    }

    public static function getAll(?string $filterDate = null, ?int $filterEstado = null, ?int $filterEsteticista = null, ?int $filterServicio = null, ?string $filterCliente = null): array {
        $db = getDB();
        [$where, $params] = self::adminFilters($filterDate, $filterEstado, $filterEsteticista, $filterServicio, $filterCliente);

        $sql = 'SELECT r.id_reserva, r.id_cliente, r.id_esteticista, r.id_servicio,
                       r.fecha_hora_inicio, r.fecha_hora_fin, r.creado_en,
                       s.nombre_servicio, s.duracion_minutos, s.precio,
                       er.nombre_estado, er.id_estado,
                       est.nombre AS nombre_esteticista,
                       cli.nombre AS nombre_cliente, cli.correo AS correo_cliente, cli.telefono AS telefono_cliente
                FROM reservas r
                JOIN servicios s ON r.id_servicio = s.id_servicio
                JOIN estados_reserva er ON r.id_estado = er.id_estado
                JOIN usuarios est ON r.id_esteticista = est.id_usuario
                JOIN usuarios cli ON r.id_cliente = cli.id_usuario';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY r.fecha_hora_inicio DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getPaginated(int $page, int $limit, ?string $filterDate = null, ?int $filterEstado = null, ?int $filterEsteticista = null, ?int $filterServicio = null, ?string $filterCliente = null): array {
        $db = getDB();
        [$where, $params] = self::adminFilters($filterDate, $filterEstado, $filterEsteticista, $filterServicio, $filterCliente);
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $count = $db->prepare(
            'SELECT COUNT(*)
             FROM reservas r
             JOIN usuarios cli ON r.id_cliente = cli.id_usuario' . $whereSql
        );
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $page = max(1, $page);
        $limit = max(1, min($limit, 100));
        $totalPages = max(1, (int) ceil($total / $limit));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $limit;

        $sql = 'SELECT r.id_reserva, r.id_cliente, r.id_esteticista, r.id_servicio,
                       r.fecha_hora_inicio, r.fecha_hora_fin, r.creado_en,
                       s.nombre_servicio, s.duracion_minutos, s.precio,
                       er.nombre_estado, er.id_estado,
                       est.nombre AS nombre_esteticista,
                       cli.nombre AS nombre_cliente, cli.correo AS correo_cliente, cli.telefono AS telefono_cliente
                FROM reservas r
                JOIN servicios s ON r.id_servicio = s.id_servicio
                JOIN estados_reserva er ON r.id_estado = er.id_estado
                JOIN usuarios est ON r.id_esteticista = est.id_usuario
                JOIN usuarios cli ON r.id_cliente = cli.id_usuario' .
                $whereSql .
                ' ORDER BY r.fecha_hora_inicio DESC LIMIT :limit OFFSET :offset';

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'reservas' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Actualiza el estado de una reserva (para administracion).
     */
    public static function updateStatus(int $id, int $nuevoEstado, ?int $userId = null, ?string $motivo = null): bool {
        $reserva = self::findById($id);
        if (!$reserva) {
            return false;
        }

        $estadoAnterior = (int) $reserva['id_estado'];
        if ($estadoAnterior === $nuevoEstado) {
            return true; // Ya tenia ese estado
        }

        $db = getDB();
        $motivoTexto = $motivo ?: "Actualizacion de estado de {$reserva['nombre_estado']} a nuevo estado ({$nuevoEstado})";
        $modificadorId = $userId ?: (int) $reserva['id_cliente'];

        // Configurar variables para el trigger
        $db->prepare('SET @app_usuario_modifica = ?, @app_motivo = ?')->execute([$modificadorId, $motivoTexto]);

        $stmt = $db->prepare('UPDATE reservas SET id_estado = :estado WHERE id_reserva = :id');
        $ok = $stmt->execute(['estado' => $nuevoEstado, 'id' => $id]);

        if ($ok && $stmt->rowCount() > 0) {
            log_audit($modificadorId, 'UPDATE_STATUS', 'reservas', $id, "Estado actualizado de {$estadoAnterior} a {$nuevoEstado}. Motivo: {$motivoTexto}");
            return true;
        }

        return false;
    }

    /**
     * Reprograma una cita de manera atomica.
     * Valida disponibilidad de esteticista y cliente, actualiza horarios/profesional,
     * registra en historial_estados y logs_auditoria.
     */
    public static function reschedule(int $reservaId, string $newStart, string $newEnd, int $newEsteticId, int $userId, ?string $motivo = null): bool {
        $reserva = self::findById($reservaId);
        if (!$reserva) {
            throw new \InvalidArgumentException('No se encontro la reserva indicada.');
        }

        if (strtotime($newStart) >= strtotime($newEnd)) {
            throw new \DomainException('La fecha y hora de fin debe ser posterior a la de inicio.');
        }

        if (strtotime($newStart) <= time()) {
            throw new \DomainException('La reprogramacion debe realizarse para una fecha y hora futura.');
        }

        $db = getDB();

        // 1. Validar choque con otras reservas activas del esteticista destino
        $stmtChoqueRes = $db->prepare(
            'SELECT COUNT(*) FROM reservas
             WHERE id_esteticista = :est_id
               AND id_reserva <> :res_id
               AND id_estado NOT IN (4, 6)
               AND :start < fecha_hora_fin
               AND :end > fecha_hora_inicio'
        );
        $stmtChoqueRes->execute([
            'est_id' => $newEsteticId,
            'res_id' => $reservaId,
            'start'  => $newStart,
            'end'    => $newEnd,
        ]);
        if (((int) $stmtChoqueRes->fetchColumn()) > 0) {
            throw new \DomainException('El especialista seleccionado ya tiene una reserva en ese horario.');
        }

        // 2. Validar choque con bloqueos de agenda del esteticista
        $stmtChoqueBloq = $db->prepare(
            'SELECT COUNT(*) FROM ausencias_bloqueos
             WHERE id_esteticista = :est_id
               AND :start < fecha_hora_fin
               AND :end > fecha_hora_inicio'
        );
        $stmtChoqueBloq->execute([
            'est_id' => $newEsteticId,
            'start'  => $newStart,
            'end'    => $newEnd,
        ]);
        if (((int) $stmtChoqueBloq->fetchColumn()) > 0) {
            throw new \DomainException('El especialista tiene un bloqueo de agenda en ese horario.');
        }

        // 3. Validar choque del cliente con otra de sus citas
        if (self::hasClientConflict((int) $reserva['id_cliente'], $newStart, $newEnd, $reservaId)) {
            throw new \DomainException('El cliente ya tiene otra cita activa agendada en ese mismo horario.');
        }

        // 4. Actualizacion atomica en BD
        $motivoTexto = $motivo ?: "Reprogramada de {$reserva['fecha_hora_inicio']} a {$newStart}";
        $estadoActual = (int) $reserva['id_estado'];
        // Si estaba Pendiente (1) o Confirmada (2), podemos mantener el estado o pasar a Reasignada (5) si cambio especialista
        $nuevoEstado = ($newEsteticId !== (int) $reserva['id_esteticista']) ? 5 : $estadoActual;

        $db->beginTransaction();
        try {
            // Establecer variables de sesion para el trigger si el estado cambia
            $db->prepare('SET @app_usuario_modifica = ?, @app_motivo = ?')->execute([$userId, $motivoTexto]);

            $stmtUpd = $db->prepare(
                'UPDATE reservas
                 SET fecha_hora_inicio = :inicio,
                     fecha_hora_fin    = :fin,
                     id_esteticista    = :est_id,
                     id_estado         = :estado
                 WHERE id_reserva = :res_id'
            );
            $stmtUpd->execute([
                'inicio'  => $newStart,
                'fin'     => $newEnd,
                'est_id'  => $newEsteticId,
                'estado'  => $nuevoEstado,
                'res_id'  => $reservaId,
            ]);

            // Si el estado no cambio, el trigger no se dispara, por lo que registramos el evento manualmente
            if ($nuevoEstado === $estadoActual) {
                $stmtHist = $db->prepare(
                    'INSERT INTO historial_estados (id_reserva, id_estado_anterior, id_estado_nuevo, id_usuario_modifica, motivo, fecha_cambio)
                     VALUES (:res_id, :est_ant, :est_nue, :user_id, :motivo, NOW())'
                );
                $stmtHist->execute([
                    'res_id'   => $reservaId,
                    'est_ant'  => $estadoActual,
                    'est_nue'  => $nuevoEstado,
                    'user_id'  => $userId,
                    'motivo'   => $motivoTexto,
                ]);
            }

            log_audit(
                $userId,
                'RESCHEDULE_APPOINTMENT',
                'reservas',
                $reservaId,
                "Reprogramada: [{$reserva['fecha_hora_inicio']} -> {$newStart}], Esteticista: [{$reserva['id_esteticista']} -> {$newEsteticId}]. Motivo: {$motivoTexto}"
            );

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Consulta el historial de estados y trazabilidad de una reserva.
     */
    public static function getHistory(int $reservaId): array {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT h.id_historial, h.id_reserva, h.fecha_cambio, h.motivo,
                    ea.nombre_estado AS estado_anterior,
                    en.nombre_estado AS estado_nuevo,
                    u.nombre AS usuario_modifica,
                    u.id_rol AS rol_usuario_modifica,
                    r.nombre_rol AS nombre_rol_modifica
             FROM historial_estados h
             LEFT JOIN estados_reserva ea ON h.id_estado_anterior = ea.id_estado
             JOIN estados_reserva en ON h.id_estado_nuevo = en.id_estado
             LEFT JOIN usuarios u ON h.id_usuario_modifica = u.id_usuario
             LEFT JOIN roles r ON u.id_rol = r.id_rol
             WHERE h.id_reserva = :reserva_id
             ORDER BY h.fecha_cambio DESC, h.id_historial DESC'
        );
        $stmt->execute(['reserva_id' => $reservaId]);
        return $stmt->fetchAll();
    }

    /**
     * Cuenta reservas agrupadas por estado.
     */
    public static function countByStatus(): array {
        $db = getDB();
        $stmt = $db->query(
            'SELECT er.nombre_estado, COUNT(*) AS total
             FROM reservas r
             JOIN estados_reserva er ON r.id_estado = er.id_estado
             GROUP BY er.nombre_estado'
        );
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['nombre_estado']] = (int) $row['total'];
        }
        return $result;
    }

    /**
     * Calcula los slots horarios disponibles para una fecha y duracion.
     */
    public static function getAvailableSlots(string $date, int $duracionMin, ?int $esteticId = null): array {
        $db = getDB();
        $slots = ['10:00 am','11:00 am','12:00 pm','2:00 pm','3:00 pm','4:00 pm','5:00 pm'];

        require_once __DIR__ . '/User.php';

        if ($esteticId && $esteticId > 0) {
            $especialistas = [$esteticId];
        } else {
            $rows = User::getEsteticistas();
            $especialistas = array_column($rows, 'id_usuario');
        }

        if (empty($especialistas)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($especialistas), '?'));

        $sqlReservas = "SELECT id_esteticista, fecha_hora_inicio, fecha_hora_fin
            FROM reservas
            WHERE DATE(fecha_hora_inicio) = ?
              AND id_estado NOT IN (4, 6)
              AND id_esteticista IN ($placeholders)";

        $params = array_merge([$date], $especialistas);
        $stmt = $db->prepare($sqlReservas);
        $stmt->execute($params);
        $reservas = $stmt->fetchAll();

        $sqlBloqueos = "SELECT id_esteticista, fecha_hora_inicio, fecha_hora_fin
            FROM ausencias_bloqueos
            WHERE DATE(fecha_hora_inicio) = ?
              AND id_esteticista IN ($placeholders)";

        $stmt2 = $db->prepare($sqlBloqueos);
        $stmt2->execute($params);
        $bloqueos = $stmt2->fetchAll();

        $occupied = array_merge($reservas, $bloqueos);

        $available = [];
        foreach ($slots as $slotLabel) {
            $slotStart = date_create_from_format('Y-m-d g:i a', $date . ' ' . strtolower($slotLabel));
            if (!$slotStart) continue;
            $slotEnd = clone $slotStart;
            $slotEnd->modify("+{$duracionMin} minutes");

            if ($esteticId && $esteticId > 0) {
                if (!self::hasConflict($esteticId, $slotStart, $slotEnd, $occupied)) {
                    $available[] = $slotLabel;
                }
            } else {
                foreach ($especialistas as $eid) {
                    if (!self::hasConflict($eid, $slotStart, $slotEnd, $occupied)) {
                        $available[] = $slotLabel;
                        break;
                    }
                }
            }
        }

        return $available;
    }

    private static function hasConflict(int $estId, \DateTime $start, \DateTime $end, array $occupied): bool {
        foreach ($occupied as $o) {
            if ((int) $o['id_esteticista'] !== $estId) continue;
            $oStart = new \DateTime($o['fecha_hora_inicio']);
            $oEnd   = new \DateTime($o['fecha_hora_fin']);
            if ($start < $oEnd && $end > $oStart) {
                return true;
            }
        }
        return false;
    }

    /**
     * Metricas agregadas para el informe ejecutivo.
     * Rango opcional (Y-m-d) sobre la fecha de inicio de la reserva.
     */
    public static function getReportMetrics(?string $desde = null, ?string $hasta = null): array {
        $db = getDB();

        $where  = [];
        $params = [];
        if ($desde) {
            $where[] = 'DATE(r.fecha_hora_inicio) >= :desde';
            $params['desde'] = $desde;
        }
        if ($hasta) {
            $where[] = 'DATE(r.fecha_hora_inicio) <= :hasta';
            $params['hasta'] = $hasta;
        }
        $whereClause = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        // 1. Totales e ingresos (reales = Completada 3; proyectados = todo salvo Cancelada 4 y No_Show 6)
        $sqlTotales = "SELECT
                COUNT(*) AS total_citas,
                COALESCE(SUM(CASE WHEN r.id_estado = 3 THEN s.precio ELSE 0 END), 0) AS ingresos_reales,
                COALESCE(SUM(CASE WHEN r.id_estado NOT IN (4, 6) THEN s.precio ELSE 0 END), 0) AS ingresos_proyectados
            FROM reservas r
            JOIN servicios s ON r.id_servicio = s.id_servicio
            $whereClause";
        $stmt = $db->prepare($sqlTotales);
        $stmt->execute($params);
        $totales = $stmt->fetch() ?: [];

        // 2. Conteo por estado
        $sqlEstados = "SELECT er.nombre_estado, COUNT(*) AS total
            FROM reservas r
            JOIN estados_reserva er ON r.id_estado = er.id_estado
            $whereClause
            GROUP BY er.id_estado, er.nombre_estado
            ORDER BY er.id_estado";
        $stmt = $db->prepare($sqlEstados);
        $stmt->execute($params);
        $porEstado = [];
        foreach ($stmt->fetchAll() as $row) {
            $porEstado[$row['nombre_estado']] = (int) $row['total'];
        }

        // 3. Rendimiento por especialista
        $sqlEsp = "SELECT est.nombre AS especialista,
                          COUNT(*) AS citas,
                          COALESCE(SUM(CASE WHEN r.id_estado = 3 THEN s.precio ELSE 0 END), 0) AS ingresos
            FROM reservas r
            JOIN servicios s ON r.id_servicio = s.id_servicio
            JOIN usuarios est ON r.id_esteticista = est.id_usuario
            $whereClause
            GROUP BY est.id_usuario, est.nombre
            ORDER BY citas DESC, est.nombre ASC";
        $stmt = $db->prepare($sqlEsp);
        $stmt->execute($params);
        $porEspecialista = array_map(function ($row) {
            return [
                'especialista' => $row['especialista'],
                'citas'        => (int) $row['citas'],
                'ingresos'     => (float) $row['ingresos'],
            ];
        }, $stmt->fetchAll());

        // 4. Tratamientos mas solicitados
        $sqlServ = "SELECT s.nombre_servicio,
                           COUNT(*) AS citas,
                           COALESCE(SUM(CASE WHEN r.id_estado = 3 THEN s.precio ELSE 0 END), 0) AS ingresos
            FROM reservas r
            JOIN servicios s ON r.id_servicio = s.id_servicio
            $whereClause
            GROUP BY s.id_servicio, s.nombre_servicio
            ORDER BY citas DESC, s.nombre_servicio ASC";
        $stmt = $db->prepare($sqlServ);
        $stmt->execute($params);
        $porServicio = array_map(function ($row) {
            return [
                'nombre_servicio' => $row['nombre_servicio'],
                'citas'           => (int) $row['citas'],
                'ingresos'        => (float) $row['ingresos'],
            ];
        }, $stmt->fetchAll());

        return [
            'total_citas'          => (int) ($totales['total_citas'] ?? 0),
            'ingresos_reales'      => (float) ($totales['ingresos_reales'] ?? 0),
            'ingresos_proyectados' => (float) ($totales['ingresos_proyectados'] ?? 0),
            'por_estado'           => $porEstado,
            'por_especialista'     => $porEspecialista,
            'por_servicio'         => $porServicio,
        ];
    }
}
