<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Inicia la sesion de forma segura.
 */
function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_set_cookie_params([
            'lifetime' => 0,            // Expira al cerrar navegador
            'path'     => '/',
            'secure'   => $isHttps,      // Solo HTTPS si esta disponible
            'httponly'  => true,          // No accesible desde JS
            'samesite'  => 'Strict',     // Proteccion CSRF a nivel cookie
        ]);

        session_start();
    }
}

/**
 * Verifica si el usuario esta logueado.
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Retorna los datos del usuario logueado o null.
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }

    require_once __DIR__ . '/../models/User.php';
    return User::findById($_SESSION['user_id']);
}

/**
 * Responde 401 JSON si el usuario no esta autenticado.
 */
function require_login(): void {
    if (!is_logged_in()) {
        json_response(['error' => 'Debes iniciar sesion'], 401);
    }
}

/**
 * Establece la sesion del usuario despues de login/registro.
 * Regenera el ID de sesion para prevenir session fixation.
 */
function login_session(int $userId): void {
    // Regenerar ID de sesion para prevenir session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = $userId;

    // Regenerar token CSRF post-autenticacion
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
