<?php

/**
 * Genera o retorna el token CSRF de la sesion actual.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica que el token CSRF enviado sea valido.
 */
function verify_csrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Lee un objeto JSON y rechaza cuerpos vacios, invalidos o de otro tipo. */
function json_input(): array {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input) || array_is_list($input)) {
        json_response(['error' => 'Solicitud JSON invalida'], 400);
    }
    return $input;
}

/**
 * Escapa una cadena para salida HTML segura.
 */
function sanitize(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Envia una respuesta JSON con el codigo de estado indicado.
 * Incluye headers de seguridad y no-cache para datos sensibles.
 */
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Redirige a la URL indicada.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
