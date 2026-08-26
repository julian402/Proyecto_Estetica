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
        // id_estado 4 = Cancelada
        $stmt = $db->prepare(
            'UPDATE reservas SET id_estado = 4
             WHERE id_reserva = :id AND id_cliente = :user_id AND id_estado = 1'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
