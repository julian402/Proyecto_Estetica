<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/Appointment.php';

start_session();
require_login();

$user = current_user();
$rol = (int) ($user['id_rol'] ?? 0);

if (!in_array($rol, [2, 3])) {
    json_response(['error' => 'No tienes permiso para generar informes'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

$desde = !empty($_GET['desde']) ? trim($_GET['desde']) : null;
$hasta = !empty($_GET['hasta']) ? trim($_GET['hasta']) : null;

$metrics = Appointment::getReportMetrics($desde, $hasta);

// Citas en el periodo seleccionado para la tabla de desglose
$db = getDB();
$where = [];
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
$sql = "SELECT r.id_reserva, r.fecha_hora_inicio, s.nombre_servicio, s.precio,
               er.nombre_estado, est.nombre AS nombre_esteticista, cli.nombre AS nombre_cliente
        FROM reservas r
        JOIN servicios s ON r.id_servicio = s.id_servicio
        JOIN estados_reserva er ON r.id_estado = er.id_estado
        JOIN usuarios est ON r.id_esteticista = est.id_usuario
        JOIN usuarios cli ON r.id_cliente = cli.id_usuario
        $whereClause
        ORDER BY r.fecha_hora_inicio DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$citasPeriodo = $stmt->fetchAll();

json_response([
    'success' => true,
    'metrics' => $metrics,
    'citas'   => $citasPeriodo,
    'periodo' => [
        'desde' => $desde ?: 'Historico completo',
        'hasta' => $hasta ?: date('Y-m-d')
    ]
]);
