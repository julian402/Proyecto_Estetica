<?php
require_once __DIR__ . '/../includes/db.php';

class Appointment {
    /**
     * Crea una nueva reserva.
     * $fechaInicio y $fechaFin deben estar en formato 'Y-m-d H:i:s'.
     */
    public static function create(int $clienteId, int $esteticId, int $servicioId, string $fechaInicio, string $fechaFin): int {
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
        return (int) $db->lastInsertId();
    }

    /**
     * Retorna las reservas de un cliente con datos del servicio y esteticista.
     */
    public static function getByUser(int $userId): array {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT r.id_reserva, r.fecha_hora_inicio, r.fecha_hora_fin, r.creado_en,
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
     * Cancela una reserva verificando que pertenezca al cliente.
     * Solo se puede cancelar si esta en estado Pendiente (id_estado = 1).
     */
    public static function cancel(int $id, int $userId): bool {
        $db = getDB();
        $stmt = $db->prepare(
            'UPDATE reservas SET id_estado = 4
             WHERE id_reserva = :id AND id_cliente = :user_id AND id_estado = 1'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Retorna todas las reservas (para admin/recepcion).
     */
    public static function getAll(?string $filterDate = null, ?int $filterEstado = null, ?int $filterEsteticista = null): array {
        $db = getDB();
        $where = [];
        $params = [];

        if ($filterDate) {
            $where[] = 'DATE(r.fecha_hora_inicio) = :fecha';
            $params['fecha'] = $filterDate;
        }
        if ($filterEstado) {
            $where[] = 'r.id_estado = :id_estado';
            $params['id_estado'] = $filterEstado;
        }
        if ($filterEsteticista) {
            $where[] = 'r.id_esteticista = :id_esteticista';
            $params['id_esteticista'] = $filterEsteticista;
        }

        $sql = 'SELECT r.id_reserva, r.fecha_hora_inicio, r.fecha_hora_fin, r.creado_en,
                       s.nombre_servicio, s.duracion_minutos, s.precio,
                       er.nombre_estado, er.id_estado,
                       est.nombre AS nombre_esteticista,
                       cli.nombre AS nombre_cliente, cli.correo AS correo_cliente
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

    /**
     * Actualiza el estado de una reserva (para admin/recepcion).
     */
    public static function updateStatus(int $id, int $nuevoEstado): bool {
        $db = getDB();
        $stmt = $db->prepare(
            'UPDATE reservas SET id_estado = :estado WHERE id_reserva = :id'
        );
        $stmt->execute(['estado' => $nuevoEstado, 'id' => $id]);
        return $stmt->rowCount() > 0;
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

    public static function getAvailableSlots(string $date, int $duracionMin, ?int $esteticId = null): array {
        $db = getDB();
        $slots = ['10:00 am','11:00 am','12:00 pm','2:00 pm','3:00 pm','4:00 pm','5:00 pm'];
        $activeStates = [1, 2]; // Pendiente, Confirmada

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
              AND id_estado IN (1, 2)
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
}
