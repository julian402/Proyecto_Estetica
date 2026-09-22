<?php
require_once __DIR__ . '/../includes/db.php';

class Treatment {
    /**
     * Retorna todos los servicios activos con su categoria y subcategoria.
     */
    public static function getAll(): array {
        $db = getDB();
        $stmt = $db->query(
            'SELECT s.id_servicio, s.nombre_servicio, s.descripcion,
                    s.duracion_minutos, s.precio, s.activo, s.imagen_url,
                    c.nombre_categoria, sub.nombre_subcategoria, s.id_subcategoria
             FROM servicios s
             JOIN subcategorias sub ON s.id_subcategoria = sub.id_subcategoria
             JOIN categorias c ON sub.id_categoria = c.id_categoria
             WHERE s.activo = 1
             ORDER BY s.id_servicio'
        );
        return $stmt->fetchAll();
    }

    /**
     * Retorna todos los servicios (activos e inactivos) para administracion.
     */
    public static function getAllAdmin(): array {
        $db = getDB();
        $stmt = $db->query(
            'SELECT s.id_servicio, s.nombre_servicio, s.descripcion,
                    s.duracion_minutos, s.precio, s.activo, s.imagen_url,
                    c.id_categoria, c.nombre_categoria,
                    sub.id_subcategoria, sub.nombre_subcategoria
             FROM servicios s
             JOIN subcategorias sub ON s.id_subcategoria = sub.id_subcategoria
             JOIN categorias c ON sub.id_categoria = c.id_categoria
             ORDER BY c.nombre_categoria ASC, sub.nombre_subcategoria ASC, s.nombre_servicio ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Filtra servicios por nombre de categoria (Facial / Corporal).
     */
    public static function getByCategory(string $category): array {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT s.id_servicio, s.nombre_servicio, s.descripcion,
                    s.duracion_minutos, s.precio, s.activo, s.imagen_url,
                    c.nombre_categoria, sub.nombre_subcategoria, s.id_subcategoria
             FROM servicios s
             JOIN subcategorias sub ON s.id_subcategoria = sub.id_subcategoria
             JOIN categorias c ON sub.id_categoria = c.id_categoria
             WHERE c.nombre_categoria = :category AND s.activo = 1
             ORDER BY s.id_servicio'
        );
        $stmt->execute(['category' => $category]);
        return $stmt->fetchAll();
    }

    /**
     * Busca un servicio por ID.
     */
    public static function findById(int $id): ?array {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT s.id_servicio, s.id_subcategoria, s.nombre_servicio, s.descripcion,
                    s.duracion_minutos, s.precio, s.activo, s.imagen_url,
                    c.id_categoria, c.nombre_categoria, sub.nombre_subcategoria
             FROM servicios s
             JOIN subcategorias sub ON s.id_subcategoria = sub.id_subcategoria
             JOIN categorias c ON sub.id_categoria = c.id_categoria
             WHERE s.id_servicio = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $t = $stmt->fetch();
        return $t ?: null;
    }

    /**
     * Crea un nuevo servicio.
     */
    public static function create(int $subcatId, string $nombre, ?string $descripcion, int $duracion, float $precio, bool $activo = true, ?string $imagenUrl = null): int {
        if ($duracion <= 0) {
            throw new \InvalidArgumentException('La duracion debe ser mayor a 0 minutos.');
        }
        if ($precio < 0) {
            throw new \InvalidArgumentException('El precio no puede ser negativo.');
        }

        $db = getDB();
        $stmt = $db->prepare(
            'INSERT INTO servicios (id_subcategoria, nombre_servicio, descripcion, duracion_minutos, precio, activo, imagen_url)
             VALUES (:subcat, :nombre, :descripcion, :duracion, :precio, :activo, :imagen_url)'
        );
        $stmt->execute([
            'subcat'      => $subcatId,
            'nombre'      => trim($nombre),
            'descripcion' => $descripcion ? trim($descripcion) : null,
            'duracion'    => $duracion,
            'precio'      => $precio,
            'activo'      => $activo ? 1 : 0,
            'imagen_url'  => $imagenUrl,
        ]);
        return (int) $db->lastInsertId();
    }

    /**
     * Actualiza un servicio existente.
     */
    public static function update(int $id, int $subcatId, string $nombre, ?string $descripcion, int $duracion, float $precio, ?bool $activo = null, ?string $imagenUrl = null): bool {
        if ($duracion <= 0) {
            throw new \InvalidArgumentException('La duracion debe ser mayor a 0 minutos.');
        }
        if ($precio < 0) {
            throw new \InvalidArgumentException('El precio no puede ser negativo.');
        }

        $db = getDB();

        $fields = [
            'id_subcategoria = :subcat',
            'nombre_servicio = :nombre',
            'descripcion = :descripcion',
            'duracion_minutos = :duracion',
            'precio = :precio',
        ];
        $params = [
            'subcat'      => $subcatId,
            'nombre'      => trim($nombre),
            'descripcion' => $descripcion ? trim($descripcion) : null,
            'duracion'    => $duracion,
            'precio'      => $precio,
            'id'          => $id,
        ];

        if ($activo !== null) {
            $fields[] = 'activo = :activo';
            $params['activo'] = $activo ? 1 : 0;
        }
        if ($imagenUrl !== null) {
            $fields[] = 'imagen_url = :imagen_url';
            $params['imagen_url'] = $imagenUrl;
        }

        $sql = 'UPDATE servicios SET ' . implode(', ', $fields) . ' WHERE id_servicio = :id';
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Activa o desactiva un servicio.
     */
    public static function toggleActive(int $id, ?bool $activo = null): bool {
        $db = getDB();
        if ($activo !== null) {
            $stmt = $db->prepare('UPDATE servicios SET activo = :activo WHERE id_servicio = :id');
            return $stmt->execute(['activo' => $activo ? 1 : 0, 'id' => $id]);
        } else {
            $stmt = $db->prepare('UPDATE servicios SET activo = NOT activo WHERE id_servicio = :id');
            return $stmt->execute(['id' => $id]);
        }
    }

    /**
     * Retorna todas las subcategorias disponibles agrupadas por categoria.
     */
    public static function getAllSubcategories(): array {
        $db = getDB();
        $stmt = $db->query(
            'SELECT sub.id_subcategoria, sub.nombre_subcategoria,
                    c.id_categoria, c.nombre_categoria
             FROM subcategorias sub
             JOIN categorias c ON sub.id_categoria = c.id_categoria
             ORDER BY c.nombre_categoria ASC, sub.nombre_subcategoria ASC'
        );
        return $stmt->fetchAll();
    }
}
