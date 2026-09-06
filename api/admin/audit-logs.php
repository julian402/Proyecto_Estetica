<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

start_session();

// Solo SuperAdmin (2) - HU07
$user = require_role(2);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

$db = getDB();

$filterUser   = !empty($_GET['user_id']) ? (int) $_GET['user_id'] : null;
$filterAction = !empty($_GET['action']) ? trim($_GET['action']) : null;
$filterTable  = !empty($_GET['table']) ? trim($_GET['table']) : null;
$filterDate   = !empty($_GET['date']) ? trim($_GET['date']) : null;
$limit        = min(200, max(1, (int) ($_GET['limit'] ?? 50)));
$offset       = max(0, (int) ($_GET['offset'] ?? 0));

$where = [];
$params = [];

if ($filterUser) {
    $where[] = 'l.id_usuario = :user_id';
    $params['user_id'] = $filterUser;
}
if ($filterAction) {
    $where[] = 'l.accion LIKE :action';
    $params['action'] = "%{$filterAction}%";
}
if ($filterTable) {
    $where[] = 'l.tabla_afectada = :table';
    $params['table'] = $filterTable;
}
if ($filterDate) {
    $where[] = 'DATE(l.fecha_hora) = :date';
    $params['date'] = $filterDate;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Conteo total
$stmtCount = $db->prepare("SELECT COUNT(*) FROM logs_auditoria l {$whereClause}");
$stmtCount->execute($params);
$total = (int) $stmtCount->fetchColumn();

// Consulta paginada
$sql = "SELECT l.id_log, l.id_usuario, l.accion, l.tabla_afectada, l.registro_id,
               l.detalles, l.ip, l.fecha_hora,
               u.nombre AS nombre_usuario, u.correo AS correo_usuario,
               r.nombre_rol
        FROM logs_auditoria l
        LEFT JOIN usuarios u ON l.id_usuario = u.id_usuario
        LEFT JOIN roles r ON u.id_rol = r.id_rol
        {$whereClause}
        ORDER BY l.fecha_hora DESC, l.id_log DESC
        LIMIT {$limit} OFFSET {$offset}";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

json_response([
    'success' => true,
    'total'   => $total,
    'limit'   => $limit,
    'offset'  => $offset,
    'logs'    => $logs,
]);
