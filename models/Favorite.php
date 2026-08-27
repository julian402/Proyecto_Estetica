<?php
require_once __DIR__ . '/../includes/db.php';

class Favorite {
    public static function toggle(int $userId, int $servicioId): bool {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT id_favorito FROM favoritos WHERE id_usuario = :uid AND id_servicio = :sid'
        );
        $stmt->execute(['uid' => $userId, 'sid' => $servicioId]);
        $exists = $stmt->fetch();

        if ($exists) {
            $db->prepare('DELETE FROM favoritos WHERE id_favorito = :id')
               ->execute(['id' => $exists['id_favorito']]);
            return false;
        }

        $db->prepare('INSERT INTO favoritos (id_usuario, id_servicio) VALUES (:uid, :sid)')
           ->execute(['uid' => $userId, 'sid' => $servicioId]);
        return true;
    }

    public static function getByUser(int $userId): array {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT f.id_favorito, f.creado_en,
                    s.id_servicio, s.nombre_servicio, s.descripcion,
                    s.duracion_minutos, s.precio,
                    c.nombre_categoria
             FROM favoritos f
             JOIN servicios s ON f.id_servicio = s.id_servicio
             JOIN subcategorias sc ON s.id_subcategoria = sc.id_subcategoria
             JOIN categorias c ON sc.id_categoria = c.id_categoria
             WHERE f.id_usuario = :uid
             ORDER BY f.creado_en DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function getUserFavoriteIds(int $userId): array {
        $db = getDB();
        $stmt = $db->prepare('SELECT id_servicio FROM favoritos WHERE id_usuario = :uid');
        $stmt->execute(['uid' => $userId]);
        return array_column($stmt->fetchAll(), 'id_servicio');
    }
}
