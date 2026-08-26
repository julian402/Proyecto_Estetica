<?php

// Verificar que el archivo de configuracion exista
$configFile = __DIR__ . '/../config/database.php';
if (!file_exists($configFile)) {
    header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/setup.php');
    exit;
}

require_once $configFile;

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
