<?php
require_once __DIR__ . '/../includes/db.php';

class User {
    /**
     * Busca un usuario activo por correo electronico.
     */
    public static function findByEmail(string $email): ?array {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT * FROM usuarios WHERE correo = :correo AND estado_cuenta = 1 LIMIT 1'
        );
        $stmt->execute(['correo' => strtolower(trim($email))]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Busca un usuario por correo electronico independientemente del estado de la cuenta.
     */
    public static function findByEmailAny(string $email): ?array {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM usuarios WHERE correo = :correo LIMIT 1');
        $stmt->execute(['correo' => strtolower(trim($email))]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Busca un usuario por ID.
     */
    public static function findById(int $id): ?array {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT id_usuario, id_rol, nombre, correo, telefono, es_invitado, estado_cuenta, creado_en
             FROM usuarios WHERE id_usuario = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Crea un nuevo usuario regular con rol Cliente (id_rol = 1).
     */
    public static function create(string $name, string $email, string $password, ?string $telefono = null): int {
        $db = getDB();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            'INSERT INTO usuarios (id_rol, nombre, correo, password_hash, telefono, es_invitado, estado_cuenta)
             VALUES (:id_rol, :nombre, :correo, :password_hash, :telefono, 0, 1)'
        );
        $stmt->execute([
            'id_rol'        => 1, // Cliente
            'nombre'        => trim($name),
            'correo'        => strtolower(trim($email)),
            'password_hash' => $hash,
            'telefono'      => $telefono ? trim($telefono) : null,
        ]);
        return (int) $db->lastInsertId();
    }

    /**
     * Busca o crea un usuario en modo invitado (es_invitado = 1).
     */
    public static function findOrCreateGuest(string $name, string $email, ?string $telefono = null): int {
        $existing = self::findByEmailAny($email);
        if ($existing) {
            // Si ya existe y se le proporciona telefono, actualizarlo si no tenia
            if (!empty($telefono) && empty($existing['telefono'])) {
                $db = getDB();
                $upd = $db->prepare('UPDATE usuarios SET telefono = :tel WHERE id_usuario = :id');
                $upd->execute(['tel' => trim($telefono), 'id' => $existing['id_usuario']]);
            }
            return (int) $existing['id_usuario'];
        }

        $db = getDB();
        $randomPass = bin2hex(random_bytes(16));
        $hash = password_hash($randomPass, PASSWORD_BCRYPT);

        $stmt = $db->prepare(
            'INSERT INTO usuarios (id_rol, nombre, correo, password_hash, telefono, es_invitado, estado_cuenta)
             VALUES (1, :nombre, :correo, :password_hash, :telefono, 1, 1)'
        );
        $stmt->execute([
            'nombre'        => trim($name),
            'correo'        => strtolower(trim($email)),
            'password_hash' => $hash,
            'telefono'      => $telefono ? trim($telefono) : null,
        ]);

        return (int) $db->lastInsertId();
    }

    /**
     * Completa el perfil de un usuario invitado activando su cuenta completa.
     */
    public static function completeGuestProfile(string $email, string $password, ?string $name = null, ?string $phone = null): int {
        $db = getDB();
        $user = self::findByEmailAny($email);

        $hash = password_hash($password, PASSWORD_BCRYPT);

        if ($user) {
            $updateName = !empty($name) ? trim($name) : $user['nombre'];
            $updatePhone = !empty($phone) ? trim($phone) : $user['telefono'];

            $stmt = $db->prepare(
                'UPDATE usuarios
                 SET password_hash = :hash,
                     nombre = :nombre,
                     telefono = :telefono,
                     es_invitado = 0,
                     estado_cuenta = 1
                 WHERE id_usuario = :id'
            );
            $stmt->execute([
                'hash'     => $hash,
                'nombre'   => $updateName,
                'telefono' => $updatePhone,
                'id'       => $user['id_usuario'],
            ]);
            return (int) $user['id_usuario'];
        }

        // Si no existia por alguna razon, crearlo directamente
        return self::create($name ?: 'Cliente', $email, $password, $phone);
    }

    /**
     * Retorna todos los esteticistas activos (id_rol = 4).
     */
    public static function getEsteticistas(): array {
        $db = getDB();
        $stmt = $db->query(
            'SELECT id_usuario, nombre, correo, telefono FROM usuarios
             WHERE id_rol = 4 AND estado_cuenta = 1
             ORDER BY nombre'
        );
        return $stmt->fetchAll();
    }

    // ============================================================
    // CRUD DE PERSONAL (EMPLEADOS: SuperAdmin 2, Recepcionista 3, Esteticista 4)
    // ============================================================

    /**
     * Retorna todos los empleados del sistema (roles 2, 3, 4).
     */
    public static function getAllEmployees(): array {
        $db = getDB();
        $stmt = $db->query(
            'SELECT u.id_usuario, u.id_rol, r.nombre_rol, u.nombre, u.correo,
                    u.telefono, u.estado_cuenta, u.creado_en
             FROM usuarios u
             JOIN roles r ON u.id_rol = r.id_rol
             WHERE u.id_rol IN (2, 3, 4)
             ORDER BY u.id_rol ASC, u.nombre ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Crea un nuevo empleado en el sistema.
     */
    public static function createEmployee(string $name, string $email, string $password, int $rolId, ?string $phone = null): int {
        if (!in_array($rolId, [2, 3, 4], true)) {
            throw new \InvalidArgumentException('Rol de empleado no valido.');
        }

        $emailClean = strtolower(trim($email));
        if (self::findByEmailAny($emailClean)) {
            throw new \RuntimeException('Ya existe un usuario con ese correo electronico.');
        }

        $db = getDB();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            'INSERT INTO usuarios (id_rol, nombre, correo, password_hash, telefono, es_invitado, estado_cuenta)
             VALUES (:id_rol, :nombre, :correo, :password_hash, :telefono, 0, 1)'
        );
        $stmt->execute([
            'id_rol'        => $rolId,
            'nombre'        => trim($name),
            'correo'        => $emailClean,
            'password_hash' => $hash,
            'telefono'      => $phone ? trim($phone) : null,
        ]);

        return (int) $db->lastInsertId();
    }

    /**
     * Actualiza un empleado existente.
     */
    public static function updateEmployee(int $id, string $name, string $email, int $rolId, ?string $phone = null, ?string $password = null, ?int $estadoCuenta = null): bool {
        if (!in_array($rolId, [2, 3, 4], true)) {
            throw new \InvalidArgumentException('Rol de empleado no valido.');
        }

        $db = getDB();
        $emailClean = strtolower(trim($email));

        // Verificar unicidad de correo
        $stmtCheck = $db->prepare('SELECT id_usuario FROM usuarios WHERE correo = :correo AND id_usuario <> :id');
        $stmtCheck->execute(['correo' => $emailClean, 'id' => $id]);
        if ($stmtCheck->fetch()) {
            throw new \RuntimeException('El correo electronico ya esta en uso por otro usuario.');
        }

        $fields = [
            'nombre = :nombre',
            'correo = :correo',
            'id_rol = :id_rol',
            'telefono = :telefono',
        ];
        $params = [
            'nombre'   => trim($name),
            'correo'   => $emailClean,
            'id_rol'   => $rolId,
            'telefono' => $phone ? trim($phone) : null,
            'id'       => $id,
        ];

        if (!empty($password)) {
            $fields[] = 'password_hash = :hash';
            $params['hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        if ($estadoCuenta !== null) {
            $fields[] = 'estado_cuenta = :estado_cuenta';
            $params['estado_cuenta'] = $estadoCuenta ? 1 : 0;
        }

        $sql = 'UPDATE usuarios SET ' . implode(', ', $fields) . ' WHERE id_usuario = :id';
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Elimina o desactiva un empleado.
     * Regla HU09: Bloquear eliminacion si tiene citas pendientes o confirmadas.
     */
    public static function deleteEmployee(int $id): bool {
        $db = getDB();

        // 1. Validar si tiene citas activas (Pendiente 1 o Confirmada 2)
        $stmtCitas = $db->prepare(
            'SELECT COUNT(*) FROM reservas
             WHERE (id_esteticista = :id_est OR id_cliente = :id_cli)
               AND id_estado IN (1, 2)'
        );
        $stmtCitas->execute(['id_est' => $id, 'id_cli' => $id]);
        $citasPendientes = (int) $stmtCitas->fetchColumn();

        if ($citasPendientes > 0) {
            throw new \DomainException(
                "No se puede eliminar el empleado porque tiene {$citasPendientes} cita(s) pendiente(s) o confirmada(s) asignada(s)."
            );
        }

        // 2. Comprobar si tiene registros historicos (citas pasadas, auditorias, etc.)
        $stmtHist = $db->prepare('SELECT COUNT(*) FROM reservas WHERE id_esteticista = :id');
        $stmtHist->execute(['id' => $id]);
        $totalHistorico = (int) $stmtHist->fetchColumn();

        if ($totalHistorico > 0) {
            // Desactivar cuenta para mantener integridad referencial
            $stmt = $db->prepare('UPDATE usuarios SET estado_cuenta = 0 WHERE id_usuario = :id');
            return $stmt->execute(['id' => $id]);
        }

        // Si no tiene ningun registro historico vinculado, se puede borrar fisicamente
        try {
            $stmt = $db->prepare('DELETE FROM usuarios WHERE id_usuario = :id AND id_rol IN (2, 3, 4)');
            return $stmt->execute(['id' => $id]);
        } catch (\PDOException $e) {
            // Fallback a desactivacion segura si hay restricciones de clave foranea
            $stmt = $db->prepare('UPDATE usuarios SET estado_cuenta = 0 WHERE id_usuario = :id');
            return $stmt->execute(['id' => $id]);
        }
    }
}
