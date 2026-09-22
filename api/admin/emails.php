<?php
require_once __DIR__ . '/../../includes/auth.php';

if (file_exists(__DIR__ . '/../../config/mail.php')) {
    require_once __DIR__ . '/../../config/mail.php';
}

start_session();

// Solo SuperAdmin (2)
$user = require_role([2]);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Metodo no permitido'], 405);
}

$db = getDB();

// Detalle de un correo concreto (para previsualizar el HTML)
$id = !empty($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id > 0) {
    $stmt = $db->prepare(
        'SELECT id_correo, destinatario, asunto, cuerpo_html, enviado_en, estado
         FROM correos_log WHERE id_correo = :id'
    );
    $stmt->execute(['id' => $id]);
    $correo = $stmt->fetch();

    if (!$correo) {
        json_response(['error' => 'Correo no encontrado'], 404);
    }

    json_response(['success' => true, 'correo' => $correo]);
}

// Listado (sin el cuerpo, que puede ser muy pesado)
$stmt = $db->query(
    'SELECT id_correo, destinatario, asunto, enviado_en, estado
     FROM correos_log
     ORDER BY enviado_en DESC, id_correo DESC
     LIMIT 100'
);

json_response([
    'success'   => true,
    'correos'   => $stmt->fetchAll(),
    'transport' => defined('MAIL_TRANSPORT') ? MAIL_TRANSPORT : 'log',
]);
