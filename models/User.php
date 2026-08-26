<?php
require_once __DIR__ . '/../includes/db.php';

class User {
    /**
     * Busca un usuario por correo electronico.
     */
    public static function findByEmail(string $email): ?array {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT * FROM usuarios WHERE correo = :correo AND estado_cuenta = 1 LIMIT 1'
        );
        $stmt->execute(['correo' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Crea un nuevo usuario con rol Cliente (id_rol = 1).
     */
    public static function create(string $name, string $email, string $password, ?string $telefono = null): int {
        $db = getDB();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            'INSERT INTO usuarios (id_rol, nombre, correo, password_hash, telefono)
             VALUES (:id_rol, :nombre, :correo, :password_hash, :telefono)'
        );
        $stmt->execute([
            'id_rol'        => 1, // Cliente
            'nombre'        => $name,
            'correo'        => $email,
            'password_hash' => $hash,
            'telefono'      => $telefono,
        ]);
        return (int) $db->lastInsertId();
    }

    /**
     * Busca un usuario por ID.
     */
    public static function findById(int $id): ?array {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT id_usuario, id_rol, nombre, correo, telefono, creado_en
             FROM usuarios WHERE id_usuario = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Retorna todos los esteticistas activos (id_rol = 4).
     */
    public static function getEsteticistas(): array {
        $db = getDB();
        $stmt = $db->query(
            'SELECT id_usuario, nombre FROM usuarios
             WHERE id_rol = 4 AND estado_cuenta = 1
             ORDER BY nombre'
        );
        return $stmt->fetchAll();
    }
}
