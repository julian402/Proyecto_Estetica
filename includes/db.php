<?php

// Verificar que el archivo de configuracion exista
$configFile = __DIR__ . '/../config/database.php';
if (!file_exists($configFile)) {
    header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/setup.php');
    exit;
}

require_once $configFile;

// Zona horaria de la aplicacion.
// Sin esto PHP usa la de php.ini (en XAMPP suele ser UTC o la del sistema del
// servidor) y deja de coincidir con la hora real del estudio: los horarios de hoy
// se comparan contra otra hora y se descartan como si ya hubieran pasado.
if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', 'America/Bogota');
}
date_default_timezone_set(APP_TIMEZONE);

function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // MySQL usa su propia zona horaria para NOW() y para los triggers.
            // Se alinea con la de la aplicacion para que ambas coincidan.
            try {
                $offset = (new DateTime('now', new DateTimeZone(APP_TIMEZONE)))->format('P');
                $pdo->exec("SET time_zone = '{$offset}'");
            } catch (\Throwable $e) {
                error_log('No se pudo fijar la zona horaria de MySQL: ' . $e->getMessage());
            }
        } catch (PDOException $e) {
            // Si la DB no existe, redirigir al setup
            if (strpos($e->getMessage(), 'Unknown database') !== false
                || strpos($e->getMessage(), 'Access denied') !== false
                || strpos($e->getMessage(), 'Connection refused') !== false
                || $e->getCode() == 1049) {
                header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/setup.php');
                exit;
            }
            throw $e;
        }
    }

    return $pdo;
}
