<?php

function env_value(string $key, ?string $default = null): ?string {
    static $loaded = false;

    if (!$loaded) {
        $loaded = true;
        $path = __DIR__ . '/../.env';

        if (is_readable($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                if ($name === '' || getenv($name) !== false) {
                    continue;
                }

                if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
                    $value = substr($value, 1, -1);
                }

                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
            }
        }
    }

    $value = getenv($key);
    return $value === false ? $default : $value;
}

function env_bool(string $key, bool $default = false): bool {
    $value = env_value($key);
    return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
}
