<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Inicia la sesion de forma segura con duracion extendida de 7 dias (604800 segundos).
 * Tarea 15: Evita bloqueos por inactividad.
 */
function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', '604800'); // 7 dias en el servidor

        session_set_cookie_params([
            'lifetime' => 604800,       // 7 dias en la cookie del cliente
            'path'     => '/',
            'secure'   => $isHttps,     // Solo HTTPS si esta disponible
            'httponly' => true,         // No accesible desde JavaScript
            'samesite' => 'Strict',     // Proteccion CSRF a nivel cookie
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
    return User::findById((int) $_SESSION['user_id']);
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
 * Exige que el usuario logueado tenga alguno de los roles permitidos.
 *
 * @param int|array $allowedRoles ID o arreglo de IDs de roles permitidos (1: Cliente, 2: SuperAdmin, 3: Recepcionista, 4: Esteticista)
 */
function require_role($allowedRoles): array {
    require_login();
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Usuario no encontrado'], 401);
    }

    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    $userRole = (int) ($user['id_rol'] ?? 0);

    if (!in_array($userRole, $roles, true)) {
        json_response(['error' => 'No tienes permisos para realizar esta accion'], 403);
    }

    return $user;
}

/**
 * Verifica si un usuario tiene perfil de Administrador (SuperAdmin 2 o Recepcionista 3).
 */
function is_admin(?array $user = null): bool {
    $u = $user ?? current_user();
    if (!$u) return false;
    $rol = (int) ($u['id_rol'] ?? 0);
    return in_array($rol, [2, 3], true);
}

/**
 * Verifica si un usuario es SuperAdmin (rol 2).
 */
function is_superadmin(?array $user = null): bool {
    $u = $user ?? current_user();
    if (!$u) return false;
    return (int) ($u['id_rol'] ?? 0) === 2;
}

/**
 * Verifica si un usuario es Recepcionista (rol 3).
 */
function is_receptionist(?array $user = null): bool {
    $u = $user ?? current_user();
    if (!$u) return false;
    return (int) ($u['id_rol'] ?? 0) === 3;
}

/**
 * Verifica si un usuario es Esteticista (rol 4).
 */
function is_esteticista(?array $user = null): bool {
    $u = $user ?? current_user();
    if (!$u) return false;
    return (int) ($u['id_rol'] ?? 0) === 4;
}

/**
 * Verifica si un usuario es Cliente (rol 1).
 */
function is_client(?array $user = null): bool {
    $u = $user ?? current_user();
    if (!$u) return false;
    return (int) ($u['id_rol'] ?? 0) === 1;
}

/**
 * Verifica si el usuario puede gestionar la agenda global (Admin o Recepcionista).
 */
function can_manage_appointments(?array $user = null): bool {
    return is_admin($user);
}

/**
 * Regla de negocio de cancelacion y reprogramacion:
 * Solo Admin (SuperAdmin 2 y Recepcionista 3) y el Cliente dueno de la cita (1) pueden cancelar o reprogramar.
 * Los Esteticistas (4) estan estrictamente bloqueados.
 */
function can_cancel_or_reschedule(?array $user, array $appointment): bool {
    if (!$user) return false;

    $rol = (int) ($user['id_rol'] ?? 0);

    // Administradores y Recepcionistas tienen permiso total
    if (in_array($rol, [2, 3], true)) {
        return true;
    }

    // Esteticistas estan expresamente bloqueados
    if ($rol === 4) {
        return false;
    }

    // Cliente dueno de la cita
    if ($rol === 1) {
        $clienteId = (int) ($appointment['id_cliente'] ?? 0);
        $userId    = (int) ($user['id_usuario'] ?? 0);
        return ($clienteId > 0 && $clienteId === $userId);
    }

    return false;
}

/**
 * Establece la sesion del usuario despues de login/registro.
 * Regenera el ID de sesion para prevenir session fixation.
 */
function login_session(int $userId): void {
    session_regenerate_id(true);

    $_SESSION['user_id'] = $userId;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/audit.php';

