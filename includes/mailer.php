<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/env.php';

// config/mail.php se conserva para instalaciones anteriores; la configuracion
// habitual se obtiene de las variables del sistema o del archivo .env local.
if (file_exists(__DIR__ . '/../config/mail.php')) {
    require_once __DIR__ . '/../config/mail.php';
}
require_once __DIR__ . '/smtp.php';

if (!defined('MAIL_ENABLED'))   define('MAIL_ENABLED', env_bool('MAIL_ENABLED', true));
if (!defined('MAIL_TRANSPORT')) define('MAIL_TRANSPORT', env_value('MAIL_TRANSPORT', 'log'));
if (!defined('MAIL_HOST'))      define('MAIL_HOST', env_value('MAIL_HOST', ''));
if (!defined('MAIL_PORT'))      define('MAIL_PORT', (int) env_value('MAIL_PORT', '587'));
if (!defined('MAIL_SECURE'))    define('MAIL_SECURE', env_value('MAIL_SECURE', 'tls'));
if (!defined('MAIL_USER'))      define('MAIL_USER', env_value('MAIL_USER', ''));
if (!defined('MAIL_PASS'))      define('MAIL_PASS', env_value('MAIL_PASS', ''));
if (!defined('MAIL_TIMEOUT'))   define('MAIL_TIMEOUT', (int) env_value('MAIL_TIMEOUT', '15'));
if (!defined('MAIL_FROM'))      define('MAIL_FROM', env_value('MAIL_FROM', 'no-reply@hanulbeauty.co'));
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', env_value('MAIL_FROM_NAME', 'Hanul Beauty'));
if (!defined('MAIL_REPLY_TO'))  define('MAIL_REPLY_TO', env_value('MAIL_REPLY_TO', 'soporte@hanulbeauty.co'));
if (!defined('APP_BASE_URL'))   define('APP_BASE_URL', env_value('APP_BASE_URL', 'http://localhost/Proyecto_Estetica'));

/**
 * Servicio de correo transaccional para Hanul Beauty.
 *
 * Cada correo se registra siempre en logs/emails.log y en la tabla correos_log,
 * con el resultado REAL del envio. Segun MAIL_TRANSPORT:
 *   'log'  -> solo registra (sin salir del servidor)
 *   'smtp' -> ademas entrega por SMTP mediante includes/smtp.php
 */

/**
 * Deja constancia del correo en el log de archivo y en la base de datos.
 */
function log_email(string $to, string $subject, string $htmlBody, string $estado): void {
    // 1. Registro en archivo local logs/emails.log
    try {
        $logsDir = __DIR__ . '/../logs';
        if (!is_dir($logsDir)) {
            @mkdir($logsDir, 0777, true);
        }
        $logEntry = sprintf(
            "[%s] TO: %s | SUBJECT: %s | ESTADO: %s\n----------------------------------------\n%s\n========================================\n\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $estado,
            $htmlBody
        );
        @file_put_contents($logsDir . '/emails.log', $logEntry, FILE_APPEND | LOCK_EX);
    } catch (\Throwable $e) {
        error_log('Error escribiendo en logs/emails.log: ' . $e->getMessage());
    }

    // 2. Registro en base de datos correos_log
    try {
        $db = getDB();
        $stmt = $db->prepare(
            'INSERT INTO correos_log (destinatario, asunto, cuerpo_html, enviado_en, estado)
             VALUES (:destinatario, :asunto, :cuerpo_html, NOW(), :estado)'
        );
        $stmt->execute([
            'destinatario' => $to,
            'asunto'       => $subject,
            'cuerpo_html'  => $htmlBody,
            'estado'       => mb_substr($estado, 0, 50),
        ]);
    } catch (\Throwable $e) {
        error_log('Error insertando en correos_log: ' . $e->getMessage());
    }
}

/**
 * Envia y registra un correo transaccional.
 * Devuelve true solo si el correo quedo efectivamente entregado o registrado.
 */
function send_email(string $to, string $subject, string $htmlBody): bool {
    if (!MAIL_ENABLED) {
        return false;
    }

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        log_email($to, $subject, $htmlBody, 'error: destinatario invalido');
        return false;
    }

    // Modo 'log': no se intenta ninguna entrega
    if (MAIL_TRANSPORT !== 'smtp') {
        log_email($to, $subject, $htmlBody, 'registrado');
        return true;
    }

    try {
        smtp_send($to, $subject, $htmlBody);
        log_email($to, $subject, $htmlBody, 'enviado');
        return true;
    } catch (\Throwable $e) {
        $detalle = 'error: ' . $e->getMessage();
        error_log('Fallo el envio SMTP a ' . $to . ' -> ' . $e->getMessage());
        log_email($to, $subject, $htmlBody, $detalle);
        return false;
    }
}

/**
 * Envoltura con el layout visual de Hanul Beauty.
 */
function render_hanul_email_template(string $title, string $contentHtml): string {
    $currentYear = date('Y');
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #faf9f7;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #2d2a26;
      line-height: 1.6;
    }
    .email-container {
      max-width: 600px;
      margin: 30px auto;
      background: #ffffff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
      border: 1px solid #ede8e1;
    }
    .email-header {
      background: #2d2a26;
      color: #ffffff;
      padding: 32px 30px;
      text-align: center;
    }
    .email-brand {
      font-family: 'Cormorant Garamond', Georgia, serif;
      font-size: 26px;
      letter-spacing: 2px;
      text-transform: uppercase;
      margin: 0;
      color: #f7f4ee;
    }
    .email-brand span {
      color: #c9a87c;
    }
    .email-tagline {
      font-size: 13px;
      color: #a8a29e;
      margin-top: 6px;
      letter-spacing: 1px;
    }
    .email-body {
      padding: 36px 32px;
    }
    .email-title {
      font-size: 22px;
      font-weight: 600;
      color: #1c1917;
      margin-top: 0;
      margin-bottom: 20px;
    }
    .details-box {
      background: #faf8f5;
      border: 1px solid #e8e2d9;
      border-radius: 10px;
      padding: 20px;
      margin: 24px 0;
    }
    .details-table {
      width: 100%;
      border-collapse: collapse;
    }
    .details-table td {
      padding: 8px 4px;
      font-size: 14px;
    }
    .details-label {
      color: #78716c;
      width: 38%;
      font-weight: 500;
    }
    .details-value {
      color: #1c1917;
      font-weight: 600;
    }
    .badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      background: #e6f4ea;
      color: #137333;
    }
    .badge--cancel {
      background: #fce8e6;
      color: #c5221f;
    }
    .badge--alert {
      background: #fef7e0;
      color: #b06000;
    }
    .btn-container {
      text-align: center;
      margin: 30px 0 20px;
    }
    .btn {
      display: inline-block;
      background: #c9a87c;
      color: #ffffff !important;
      text-decoration: none;
      padding: 13px 28px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
    .btn:hover {
      background: #b8955e;
    }
    .guest-banner {
      background: #fff8f0;
      border-left: 4px solid #c9a87c;
      padding: 16px 20px;
      border-radius: 0 8px 8px 0;
      margin: 24px 0;
    }
    .guest-banner h4 {
      margin: 0 0 6px;
      color: #8c6832;
      font-size: 15px;
    }
    .guest-banner p {
      margin: 0 0 12px;
      font-size: 13px;
      color: #665c54;
    }
    .email-footer {
      background: #f7f4ee;
      border-top: 1px solid #ede8e1;
      padding: 24px 30px;
      text-align: center;
      font-size: 12px;
      color: #8c827a;
    }
    .email-footer p {
      margin: 4px 0;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="email-header">
      <h1 class="email-brand">Hanul <span>Beauty</span></h1>
      <div class="email-tagline">Auténtico Cuidado Coreano</div>
    </div>
    <div class="email-body">
      {$contentHtml}
    </div>
    <div class="email-footer">
      <p><strong>Hanul Beauty Studio</strong> &bull; Bogotá, Colombia</p>
      <p>Este es un correo automático de servicio. Por favor no respondas directamente a este mensaje.</p>
      <p>&copy; {$currentYear} Hanul Beauty. Todos los derechos reservados.</p>
    </div>
  </div>
</body>
</html>
HTML;
}

/**
 * Resuelve nombre legible de estado de reserva.
 */
function get_status_name($status): string {
    $map = [
        1 => 'Pendiente',
        2 => 'Confirmada',
        3 => 'Completada',
        4 => 'Cancelada',
        5 => 'Reasignada',
        6 => 'No_Show',
    ];
    if (is_numeric($status)) {
        return $map[(int) $status] ?? 'Desconocido';
    }
    return (string) $status;
}

/**
 * Envia confirmacion de reserva (HU03).
 * Incluye invitacion directa a completar perfil si el usuario reservo como invitado.
 */
function send_appointment_confirmation(array $appointmentData, array $clientData, bool $isGuest = false): bool {
    $clientName  = htmlspecialchars($clientData['nombre'] ?? 'Estimado(a) Cliente', ENT_QUOTES, 'UTF-8');
    $clientEmail = $clientData['correo'] ?? '';
    $reservaId   = (int) ($appointmentData['id_reserva'] ?? $appointmentData['id'] ?? 0);
    $servicio    = htmlspecialchars($appointmentData['nombre_servicio'] ?? 'Tratamiento K-Beauty', ENT_QUOTES, 'UTF-8');
    $especialista= htmlspecialchars($appointmentData['nombre_esteticista'] ?? 'Especialista Hanul', ENT_QUOTES, 'UTF-8');
    $inicio      = htmlspecialchars($appointmentData['fecha_inicio'] ?? $appointmentData['fecha_hora_inicio'] ?? '', ENT_QUOTES, 'UTF-8');
    $duracion    = (int) ($appointmentData['duracion_minutos'] ?? 60);
    $precio      = number_format((float) ($appointmentData['precio'] ?? 0), 0, ',', '.');

    $guestSection = '';
    if ($isGuest) {
        $encodedEmail = urlencode($clientEmail);
        $baseUrl      = rtrim(APP_BASE_URL, "/");
        $guestSection = <<<HTML
        <div class="guest-banner">
          <h4>¿Primera vez en Hanul Beauty?</h4>
          <p>Tu cita ha sido agendada con éxito. Para consultar tus reservas, reprogramar fácilmente y acumular puntos, completa tu contraseña en un solo clic:</p>
          <a href="{$baseUrl}/completar-perfil.php?email={$encodedEmail}" class="btn" style="padding: 10px 18px; font-size: 13px;">Completar mi perfil</a>
        </div>
HTML;
    }

    $content = <<<HTML
      <h2 class="email-title">¡Tu cita ha sido reservada, {$clientName}!</h2>
      <p>Nos complace confirmar tu agendamiento en Hanul Beauty. Nuestro equipo cuidará cada detalle de tu experiencia de belleza y bienestar.</p>

      <div class="details-box">
        <table class="details-table">
          <tr>
            <td class="details-label">No. de Reserva:</td>
            <td class="details-value">#{$reservaId}</td>
          </tr>
          <tr>
            <td class="details-label">Tratamiento:</td>
            <td class="details-value">{$servicio}</td>
          </tr>
          <tr>
            <td class="details-label">Especialista:</td>
            <td class="details-value">{$especialista}</td>
          </tr>
          <tr>
            <td class="details-label">Fecha y Hora:</td>
            <td class="details-value">{$inicio}</td>
          </tr>
          <tr>
            <td class="details-label">Duración:</td>
            <td class="details-value">{$duracion} minutos</td>
          </tr>
          <tr>
            <td class="details-label">Valor:</td>
            <td class="details-value">\${$precio} COP</td>
          </tr>
          <tr>
            <td class="details-label">Estado Inicial:</td>
            <td class="details-value"><span class="badge">Pendiente</span></td>
          </tr>
        </table>
      </div>

      {$guestSection}

      <p style="font-size: 13px; color: #78716c; margin-top: 20px;">
        <em>Recomendación: Te agradecemos llegar 10 minutos antes de tu hora para iniciar puntual y disfrutar de nuestro ritual de bienvenida.</em>
      </p>
HTML;

    $subject = "Confirmación de Reserva #{$reservaId} - Hanul Beauty";
    $html = render_hanul_email_template($subject, $content);

    return send_email($clientEmail, $subject, $html);
}

/**
 * Envia notificacion de cambio de estado de reserva (Tarea 7).
 */
function send_status_change_notification(array $appointmentData, array $clientData, $oldStatus, $newStatus): bool {
    $clientName  = htmlspecialchars($clientData['nombre'] ?? 'Estimado(a) Cliente', ENT_QUOTES, 'UTF-8');
    $clientEmail = $clientData['correo'] ?? '';
    $reservaId   = (int) ($appointmentData['id_reserva'] ?? $appointmentData['id'] ?? 0);
    $servicio    = htmlspecialchars($appointmentData['nombre_servicio'] ?? 'Tratamiento K-Beauty', ENT_QUOTES, 'UTF-8');
    $inicio      = htmlspecialchars($appointmentData['fecha_inicio'] ?? $appointmentData['fecha_hora_inicio'] ?? '', ENT_QUOTES, 'UTF-8');

    $oldStatusName = htmlspecialchars(get_status_name($oldStatus), ENT_QUOTES, 'UTF-8');
    $newStatusName = htmlspecialchars(get_status_name($newStatus), ENT_QUOTES, 'UTF-8');

    $badgeClass = 'badge';
    $messageDetail = '';

    if ($newStatusName === 'Cancelada') {
        $badgeClass = 'badge badge--cancel';
        $messageDetail = '<p>Tu reserva ha sido <strong>cancelada</strong>. Si no solicitaste este cambio o deseas reagendar para otra fecha, comunícate con nosotros o ingresa a nuestra plataforma.</p>';
    } elseif ($newStatusName === 'Confirmada') {
        $messageDetail = '<p>¡Excelente! Tu cita ha sido <strong>confirmada</strong> por nuestro equipo. ¡Te esperamos en nuestro estudio!</p>';
    } elseif ($newStatusName === 'Completada') {
        $messageDetail = '<p>¡Esperamos que hayas disfrutado tu experiencia Hanul! Gracias por confiar en nosotros el cuidado de tu piel.</p>';
    } else {
        $messageDetail = "<p>El estado de tu cita ha cambiado de <strong>{$oldStatusName}</strong> a <strong>{$newStatusName}</strong>.</p>";
    }

    $content = <<<HTML
      <h2 class="email-title">Actualización de tu cita #{$reservaId}</h2>
      <p>Hola {$clientName}, te informamos que ha ocurrido una actualización en tu reserva:</p>

      <div class="details-box">
        <table class="details-table">
          <tr>
            <td class="details-label">Tratamiento:</td>
            <td class="details-value">{$servicio}</td>
          </tr>
          <tr>
            <td class="details-label">Horario:</td>
            <td class="details-value">{$inicio}</td>
          </tr>
          <tr>
            <td class="details-label">Estado Anterior:</td>
            <td class="details-value">{$oldStatusName}</td>
          </tr>
          <tr>
            <td class="details-label">Nuevo Estado:</td>
            <td class="details-value"><span class="{$badgeClass}">{$newStatusName}</span></td>
          </tr>
        </table>
      </div>

      {$messageDetail}
HTML;

    $subject = "Actualización de tu Reserva #{$reservaId} ({$newStatusName}) - Hanul Beauty";
    $html = render_hanul_email_template($subject, $content);

    return send_email($clientEmail, $subject, $html);
}

/**
 * Envia notificacion de contingencia por ausencia inesperada de esteticista (HU14).
 */
function send_contingency_notification(array $appointmentData, array $clientData, string $specialistName): bool {
    $clientName  = htmlspecialchars($clientData['nombre'] ?? 'Estimado(a) Cliente', ENT_QUOTES, 'UTF-8');
    $clientEmail = $clientData['correo'] ?? '';
    $reservaId   = (int) ($appointmentData['id_reserva'] ?? $appointmentData['id'] ?? 0);
    $servicio    = htmlspecialchars($appointmentData['nombre_servicio'] ?? 'Tratamiento K-Beauty', ENT_QUOTES, 'UTF-8');
    $inicio      = htmlspecialchars($appointmentData['fecha_inicio'] ?? $appointmentData['fecha_hora_inicio'] ?? '', ENT_QUOTES, 'UTF-8');
    $specClean   = htmlspecialchars($specialistName, ENT_QUOTES, 'UTF-8');

    $content = <<<HTML
      <h2 class="email-title">Aviso importante sobre tu cita #{$reservaId}</h2>
      <p>Hola {$clientName},</p>
      <p>Nos comunicamos contigo para informarte que debido a una eventualidad de fuerza mayor e imprevista, tu especialista habitual <strong>{$specClean}</strong> no se encontrará disponible en el horario programado:</p>

      <div class="details-box">
        <table class="details-table">
          <tr>
            <td class="details-label">No. Reserva:</td>
            <td class="details-value">#{$reservaId}</td>
          </tr>
          <tr>
            <td class="details-label">Tratamiento:</td>
            <td class="details-value">{$servicio}</td>
          </tr>
          <tr>
            <td class="details-label">Horario programado:</td>
            <td class="details-value">{$inicio}</td>
          </tr>
          <tr>
            <td class="details-label">Situación:</td>
            <td class="details-value"><span class="badge badge--alert">Contingencia de Personal</span></td>
          </tr>
        </table>
      </div>

      <p><strong>Nuestras alternativas para ti:</strong></p>
      <ul style="font-size: 14px; color: #444; padding-left: 20px; line-height: 1.8;">
        <li>Podemos reasignar tu sesión a otro especialista calificado en el mismo horario.</li>
        <li>O si lo prefieres, reprogramar tu cita sin costo alguno para el día y hora que mejor te convenga.</li>
      </ul>

      <p>Nuestro equipo de recepción te contactará de inmediato por teléfono o WhatsApp para coordinar tu preferencia con prioridad absoluta.</p>
HTML;

    $subject = "Aviso prioritario sobre tu cita #{$reservaId} - Hanul Beauty";
    $html = render_hanul_email_template($subject, $content);

    return send_email($clientEmail, $subject, $html);
}

/**
 * Envia notificacion de reprogramacion de cita (Tarea 24).
 */
function send_reschedule_notification(array $appointmentData, array $clientData, $newSlot): bool {
    $clientName  = htmlspecialchars($clientData['nombre'] ?? 'Estimado(a) Cliente', ENT_QUOTES, 'UTF-8');
    $clientEmail = $clientData['correo'] ?? '';
    $reservaId   = (int) ($appointmentData['id_reserva'] ?? $appointmentData['id'] ?? 0);
    $servicio    = htmlspecialchars($appointmentData['nombre_servicio'] ?? 'Tratamiento K-Beauty', ENT_QUOTES, 'UTF-8');
    $oldSlot     = htmlspecialchars($appointmentData['fecha_inicio'] ?? $appointmentData['fecha_hora_inicio'] ?? 'Horario anterior', ENT_QUOTES, 'UTF-8');

    $newSlotText = is_array($newSlot)
        ? htmlspecialchars(($newSlot['fecha_inicio'] ?? $newSlot['fecha_hora_inicio'] ?? json_encode($newSlot)), ENT_QUOTES, 'UTF-8')
        : htmlspecialchars((string) $newSlot, ENT_QUOTES, 'UTF-8');

    $content = <<<HTML
      <h2 class="email-title">Tu cita #{$reservaId} ha sido reprogramada</h2>
      <p>Hola {$clientName}, te confirmamos que tu cita ha sido reprogramada con éxito.</p>

      <div class="details-box">
        <table class="details-table">
          <tr>
            <td class="details-label">Tratamiento:</td>
            <td class="details-value">{$servicio}</td>
          </tr>
          <tr>
            <td class="details-label">Horario anterior:</td>
            <td class="details-value" style="text-decoration: line-through; color: #a8a29e;">{$oldSlot}</td>
          </tr>
          <tr>
            <td class="details-label">Nuevo Horario:</td>
            <td class="details-value" style="color: #1e7e46; font-size: 15px;"><strong>{$newSlotText}</strong></td>
          </tr>
        </table>
      </div>

      <p>Si tienes alguna consulta sobre este cambio, no dudes en contactarnos directamente.</p>
HTML;

    $subject = "Cita Reprogramada #{$reservaId} - Hanul Beauty";
    $html = render_hanul_email_template($subject, $content);

    return send_email($clientEmail, $subject, $html);
}

/**
 * Envia el correo de bienvenida tras crear una cuenta de cliente.
 */
function send_welcome_email(string $name, string $email): bool {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $clientName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $baseUrl    = rtrim(APP_BASE_URL, '/');

    $content = <<<HTML
      <h2 class="email-title">Bienvenida a Hanul Beauty</h2>
      <p>Hola {$clientName},</p>
      <p>Tu cuenta ya está activa. Desde ahora puedes agendar tus rituales K-Beauty, consultar el estado de tus citas, reprogramarlas y guardar tus tratamientos favoritos.</p>

      <div class="details-box">
        <table class="details-table">
          <tr>
            <td class="details-label">Correo de acceso:</td>
            <td class="details-value">{$email}</td>
          </tr>
        </table>
      </div>

      <p style="text-align:center; margin-top: 24px;">
        <a href="{$baseUrl}/index.php#agendar" class="btn" style="padding: 10px 18px; font-size: 13px;">Agendar mi primera cita</a>
      </p>

      <p style="margin-top: 24px;">Si no fuiste tú quien creó esta cuenta, escríbenos y la damos de baja de inmediato.</p>
HTML;

    return send_email(
        $email,
        'Bienvenida a Hanul Beauty',
        render_hanul_email_template('Bienvenida a Hanul Beauty', $content)
    );
}
