<?php
require_once __DIR__ . '/../includes/db.php';

class Treatment {
    /**
     * Retorna todos los servicios activos con su categoria.
     */
    public static function getAll(): array {
        $db = getDB();
        $stmt = $db->query(
            'SELECT s.id_servicio, s.nombre_servicio, s.descripcion,
                    s.duracion_minutos, s.precio,
                    c.nombre_categoria, sub.nombre_subcategoria
             FROM servicios s
             JOIN subcategorias sub ON s.id_subcategoria = sub.id_subcategoria
             JOIN categorias c ON sub.id_categoria = c.id_categoria
             WHERE s.activo = 1
             ORDER BY s.id_servicio'
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
                    s.duracion_minutos, s.precio,
                    c.nombre_categoria, sub.nombre_subcategoria
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
            'SELECT s.id_servicio, s.nombre_servicio, s.descripcion,
                    s.duracion_minutos, s.precio,
                    c.nombre_categoria, sub.nombre_subcategoria
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
}
