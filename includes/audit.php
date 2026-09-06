<?php
require_once __DIR__ . '/db.php';

/**
 * Registra una accion en la tabla de logs de auditoria.
 *
 * @param int         $userId    ID del usuario que ejecuta la accion
 * @param string      $action    Nombre de la accion (p.ej. 'CREATE_APPOINTMENT', 'CANCEL_APPOINTMENT')
 * @param string      $table     Tabla afectada (p.ej. 'reservas', 'usuarios')
 * @param int|null    $recordId  ID del registro afectado (opcional)
 * @param string|null $details   Detalles adicionales o JSON con cambios (opcional)
 * @return bool True si se inserto correctamente, False en caso contrario
 */
function log_audit(int $userId, string $action, string $table, ?int $recordId = null, ?string $details = null): bool {
    try {
        $db = getDB();

        // Obtener direccion IP del cliente
        $ip = '127.0.0.1';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($parts[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = trim($_SERVER['REMOTE_ADDR']);
        }
        $ip = substr($ip, 0, 45);

        $stmt = $db->prepare(
            'INSERT INTO logs_auditoria (id_usuario, accion, tabla_afectada, registro_id, detalles, ip, fecha_hora)
             VALUES (:id_usuario, :accion, :tabla_afectada, :registro_id, :detalles, :ip, NOW())'
        );

        return $stmt->execute([
            'id_usuario'     => $userId,
            'accion'         => mb_substr(trim($action), 0, 50),
            'tabla_afectada' => mb_substr(trim($table), 0, 50),
            'registro_id'    => $recordId,
            'detalles'       => $details,
            'ip'             => $ip,
        ]);
    } catch (\Throwable $e) {
        error_log('Error en log_audit: ' . $e->getMessage());
        return false;
    }
}
