<?php
require_once __DIR__ . '/../../includes/auth.php';

start_session();
require_login();

$user = current_user();
$rol = (int) ($user['id_rol'] ?? 0);

if ($rol !== 2) {
    json_response(['error' => 'Acceso exclusivo para SuperAdmin'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

$db = getDB();
$sql = 'SELECT l.id_log, l.id_usuario, l.accion, l.tabla_afectada, l.fecha_hora,
               u.nombre AS nombre_usuario, u.correo AS correo_usuario, r.nombre_rol
        FROM logs_auditoria l
        JOIN usuarios u ON l.id_usuario = u.id_usuario
        JOIN roles r ON u.id_rol = r.id_rol
        ORDER BY l.fecha_hora DESC
        LIMIT 100';

$stmt = $db->query($sql);
$logs = $stmt->fetchAll();

json_response([
    'success' => true,
    'logs'    => $logs
]);
