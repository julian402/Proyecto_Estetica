<?php
/**
 * Cliente SMTP minimo para Hanul Beauty.
 *
 * Habla SMTP directamente sobre sockets, sin librerias externas: el proyecto
 * no usa Composer. Cubre lo necesario para Mailtrap y Gmail:
 * EHLO -> STARTTLS -> AUTH LOGIN -> MAIL FROM -> RCPT TO -> DATA -> QUIT.
 *
 * Lanza RuntimeException con el detalle del fallo; quien llama decide
 * si lo registra o lo ignora.
 */

/**
 * Lee la respuesta del servidor. SMTP puede responder en varias lineas
 * ("250-EXTENSION"); la ultima usa un espacio tras el codigo ("250 OK").
 */
function smtp_read($socket): string {
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') {
            break;
        }
    }
    if ($response === '') {
        throw new \RuntimeException('El servidor SMTP cerro la conexion sin responder');
    }
    return $response;
}

/**
 * Envia un comando y valida que el codigo de respuesta sea el esperado.
 */
function smtp_command($socket, ?string $command, array $expectedCodes, string $stage): string {
    if ($command !== null) {
        fwrite($socket, $command . "\r\n");
    }

    $response = smtp_read($socket);
    $code = (int) substr($response, 0, 3);

    if (!in_array($code, $expectedCodes, true)) {
        throw new \RuntimeException("SMTP fallo en {$stage}: " . trim($response));
    }

    return $response;
}

/**
 * Entrega un correo HTML por SMTP.
 *
 * @throws RuntimeException si la configuracion es incompleta o el envio falla.
 */
function smtp_send(string $to, string $subject, string $htmlBody): bool {
    $host    = defined('MAIL_HOST') ? MAIL_HOST : '';
    $port    = defined('MAIL_PORT') ? (int) MAIL_PORT : 587;
    $secure  = defined('MAIL_SECURE') ? strtolower((string) MAIL_SECURE) : 'tls';
    $user    = defined('MAIL_USER') ? MAIL_USER : '';
    $pass    = defined('MAIL_PASS') ? MAIL_PASS : '';
    $timeout = defined('MAIL_TIMEOUT') ? (int) MAIL_TIMEOUT : 15;

    $from     = defined('MAIL_FROM') ? MAIL_FROM : 'no-reply@hanulbeauty.co';
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Hanul Beauty';
    $replyTo  = defined('MAIL_REPLY_TO') ? MAIL_REPLY_TO : $from;

    if ($host === '') {
        throw new \RuntimeException('MAIL_HOST no esta configurado');
    }
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        throw new \RuntimeException("Destinatario invalido: {$to}");
    }

    $transport = ($secure === 'ssl') ? 'ssl://' : '';
    $errno = 0;
    $errstr = '';

    $socket = @stream_socket_client(
        $transport . $host . ':' . $port,
        $errno,
        $errstr,
        $timeout,
        STREAM_CLIENT_CONNECT
    );

    if (!$socket) {
        throw new \RuntimeException("No se pudo conectar a {$host}:{$port} ({$errno} {$errstr})");
    }

    stream_set_timeout($socket, $timeout);

    try {
        // Saludo inicial del servidor
        smtp_command($socket, null, [220], 'conexion');

        $ehloHost = 'localhost';
        smtp_command($socket, 'EHLO ' . $ehloHost, [250], 'EHLO');

        if ($secure === 'tls') {
            smtp_command($socket, 'STARTTLS', [220], 'STARTTLS');

            $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $crypto |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }

            if (!@stream_socket_enable_crypto($socket, true, $crypto)) {
                throw new \RuntimeException('No se pudo negociar el cifrado TLS');
            }

            // Tras STARTTLS hay que repetir el EHLO
            smtp_command($socket, 'EHLO ' . $ehloHost, [250], 'EHLO (post-TLS)');
        }

        if ($user !== '') {
            smtp_command($socket, 'AUTH LOGIN', [334], 'AUTH LOGIN');
            smtp_command($socket, base64_encode($user), [334], 'usuario SMTP');
            smtp_command($socket, base64_encode($pass), [235], 'autenticacion SMTP');
        }

        smtp_command($socket, 'MAIL FROM:<' . $from . '>', [250], 'MAIL FROM');
        smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251], 'RCPT TO');
        smtp_command($socket, 'DATA', [354], 'DATA');

        $boundaryDate = date('r');
        $messageId    = sprintf('<%s@hanulbeauty.co>', bin2hex(random_bytes(12)));
        $encodedName  = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $encodedSubj  = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $headers = [
            'Date: ' . $boundaryDate,
            'Message-ID: ' . $messageId,
            'From: ' . $encodedName . ' <' . $from . '>',
            'To: <' . $to . '>',
            'Reply-To: <' . $replyTo . '>',
            'Subject: ' . $encodedSubj,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];

        $body = chunk_split(base64_encode($htmlBody), 76, "\r\n");

        // Dot-stuffing: una linea que empiece por '.' terminaria el mensaje
        $payload = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        $payload = preg_replace('/^\./m', '..', $payload);

        fwrite($socket, $payload . "\r\n.\r\n");
        smtp_command($socket, null, [250], 'entrega del mensaje');

        // QUIT es cortesia: si falla, el correo ya se entrego
        @fwrite($socket, "QUIT\r\n");
        @fclose($socket);

        return true;
    } catch (\Throwable $e) {
        @fclose($socket);
        throw $e;
    }
}
