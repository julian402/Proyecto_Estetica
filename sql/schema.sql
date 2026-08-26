-- ============================================================
-- Sistema de Gestion K-Beauty (Hanul Beauty)
-- Script de base de datos MySQL / MariaDB (compatible XAMPP)
-- Basado en el DER/MER del proyecto (CodigoUML_DER.txt / MER.txt)
-- ============================================================
-- Como ejecutar:
--   1. Abrir phpMyAdmin (XAMPP) o mysql CLI
--   2. Ejecutar este archivo completo (Importar > este .sql)
-- ============================================================

DROP DATABASE IF EXISTS kbeauty_db;
CREATE DATABASE kbeauty_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE kbeauty_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 1. ROLES
-- ============================================================
CREATE TABLE roles (
  id_rol      INT AUTO_INCREMENT PRIMARY KEY,
  nombre_rol  VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ============================================================
-- 2. USUARIOS (Cliente, Super Admin, Recepcionista, Esteticista
--    conviven en la misma tabla, diferenciados por id_rol)
-- ============================================================
CREATE TABLE usuarios (
  id_usuario     INT AUTO_INCREMENT PRIMARY KEY,
  id_rol         INT NOT NULL,
  nombre         VARCHAR(100) NOT NULL,
  correo         VARCHAR(150) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  telefono       VARCHAR(20),
  estado_cuenta  BOOLEAN NOT NULL DEFAULT TRUE,
  creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_rol
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_usuarios_rol ON usuarios(id_rol);

-- ============================================================
-- 3. CATALOGO K-BEAUTY (Categoria -> Subcategoria -> Servicio)
-- ============================================================
CREATE TABLE categorias (
  id_categoria      INT AUTO_INCREMENT PRIMARY KEY,
  nombre_categoria  VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE subcategorias (
  id_subcategoria      INT AUTO_INCREMENT PRIMARY KEY,
  id_categoria         INT NOT NULL,
  nombre_subcategoria  VARCHAR(100) NOT NULL,
  CONSTRAINT fk_subcategorias_categoria
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_subcategorias_categoria ON subcategorias(id_categoria);

CREATE TABLE servicios (
  id_servicio       INT AUTO_INCREMENT PRIMARY KEY,
  id_subcategoria   INT NOT NULL,
  nombre_servicio   VARCHAR(100) NOT NULL,
  descripcion       TEXT,
  duracion_minutos  INT NOT NULL,
  precio            DECIMAL(10,2) NOT NULL,
  activo            BOOLEAN NOT NULL DEFAULT TRUE,
  CONSTRAINT fk_servicios_subcategoria
    FOREIGN KEY (id_subcategoria) REFERENCES subcategorias(id_subcategoria)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT chk_servicios_duracion CHECK (duracion_minutos > 0),
  CONSTRAINT chk_servicios_precio CHECK (precio >= 0)
) ENGINE=InnoDB;

CREATE INDEX idx_servicios_subcategoria ON servicios(id_subcategoria);

-- ============================================================
-- 4. ESTADOS DE RESERVA
-- ============================================================
CREATE TABLE estados_reserva (
  id_estado     INT AUTO_INCREMENT PRIMARY KEY,
  nombre_estado VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ============================================================
-- 5. RESERVAS
-- ============================================================
CREATE TABLE reservas (
  id_reserva        INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente        INT NOT NULL,
  id_esteticista    INT NOT NULL,
  id_servicio       INT NOT NULL,
  id_estado         INT NOT NULL,
  fecha_hora_inicio DATETIME NOT NULL,
  fecha_hora_fin    DATETIME NOT NULL,
  creado_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reservas_cliente
    FOREIGN KEY (id_cliente) REFERENCES usuarios(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_reservas_esteticista
    FOREIGN KEY (id_esteticista) REFERENCES usuarios(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_reservas_servicio
    FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_reservas_estado
    FOREIGN KEY (id_estado) REFERENCES estados_reserva(id_estado)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_reservas_horario CHECK (fecha_hora_fin > fecha_hora_inicio)
) ENGINE=InnoDB;

CREATE INDEX idx_reservas_esteticista_horario
  ON reservas(id_esteticista, fecha_hora_inicio, fecha_hora_fin);
CREATE INDEX idx_reservas_cliente ON reservas(id_cliente);
CREATE INDEX idx_reservas_estado ON reservas(id_estado);

-- ============================================================
-- 6. AUSENCIAS / BLOQUEOS DE AGENDA (esteticista)
-- ============================================================
CREATE TABLE ausencias_bloqueos (
  id_bloqueo        INT AUTO_INCREMENT PRIMARY KEY,
  id_esteticista    INT NOT NULL,
  fecha_hora_inicio DATETIME NOT NULL,
  fecha_hora_fin    DATETIME NOT NULL,
  motivo            VARCHAR(255),
  CONSTRAINT fk_ausencias_esteticista
    FOREIGN KEY (id_esteticista) REFERENCES usuarios(id_usuario)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT chk_ausencias_horario CHECK (fecha_hora_fin > fecha_hora_inicio)
) ENGINE=InnoDB;

CREATE INDEX idx_ausencias_esteticista_horario
  ON ausencias_bloqueos(id_esteticista, fecha_hora_inicio, fecha_hora_fin);

-- ============================================================
-- 7. HISTORIAL DE ESTADOS (trazabilidad de cada reserva)
-- ============================================================
CREATE TABLE historial_estados (
  id_historial        INT AUTO_INCREMENT PRIMARY KEY,
  id_reserva          INT NOT NULL,
  id_estado_anterior  INT NULL,
  id_estado_nuevo     INT NOT NULL,
  id_usuario_modifica INT NOT NULL,
  fecha_cambio        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_historial_reserva
    FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_historial_estado_anterior
    FOREIGN KEY (id_estado_anterior) REFERENCES estados_reserva(id_estado)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_historial_estado_nuevo
    FOREIGN KEY (id_estado_nuevo) REFERENCES estados_reserva(id_estado)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_historial_usuario
    FOREIGN KEY (id_usuario_modifica) REFERENCES usuarios(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_historial_reserva ON historial_estados(id_reserva);

-- ============================================================
-- 8. LOGS DE AUDITORIA (Super Admin)
-- ============================================================
CREATE TABLE logs_auditoria (
  id_log         INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario     INT NOT NULL,
  accion         VARCHAR(50) NOT NULL,
  tabla_afectada VARCHAR(50) NOT NULL,
  fecha_hora     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_logs_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_logs_usuario ON logs_auditoria(id_usuario);

-- ============================================================
-- 9. DATOS SEMILLA (seed data)
-- ============================================================
INSERT INTO roles (nombre_rol) VALUES
  ('Cliente'), ('SuperAdmin'), ('Recepcionista'), ('Esteticista');

INSERT INTO estados_reserva (nombre_estado) VALUES
  ('Pendiente'), ('Confirmada'), ('Completada'),
  ('Cancelada'), ('Reasignada'), ('No_Show');

INSERT INTO categorias (nombre_categoria) VALUES
  ('Facial'), ('Corporal');

INSERT INTO subcategorias (id_categoria, nombre_subcategoria) VALUES
  (1, 'Hidratacion facial'),
  (1, 'Limpieza facial'),
  (2, 'Masajes corporales');

INSERT INTO servicios (id_subcategoria, nombre_servicio, descripcion, duracion_minutos, precio) VALUES
  (1, 'Glass Skin Facial', 'Hidratacion en capas y masaje drenante para una piel translucida y con luz desde adentro.', 75, 180000),
  (2, 'Limpieza Profunda K-Derm', 'Doble limpieza, extraccion suave y ampolla calmante para renovar la piel sin irritar.', 60, 140000),
  (3, 'Masaje Relajante Hanul', 'Tecnica de liberacion miofascial con aceites tibios para aliviar tension acumulada.', 60, 150000);

-- Personal de ejemplo. "!" no es un hash valido: estas cuentas no pueden
-- iniciar sesion hasta que un administrador les asigne una contrasena.
-- El administrador inicial lo crea setup.php con una clave elegida al instalar.
INSERT INTO usuarios (id_rol, nombre, correo, password_hash, telefono, estado_cuenta) VALUES
  (3, 'Recepcion Hanul', 'recepcion@hanulbeauty.co', '!', '3000000001', TRUE),
  (4, 'Sofia R.',        'sofia@hanulbeauty.co',     '!', '3000000002', TRUE),
  (4, 'Diomar A.',       'diomar@hanulbeauty.co',    '!', '3000000003', TRUE),
  (4, 'Juan D.',         'juan@hanulbeauty.co',      '!', '3000000004', TRUE);

-- ============================================================
-- 10. TRIGGERS - Validacion de doble agendamiento
-- ============================================================
DELIMITER $$

CREATE TRIGGER trg_reservas_before_insert
BEFORE INSERT ON reservas
FOR EACH ROW
BEGIN
  DECLARE v_choques INT DEFAULT 0;

  -- Choque contra otras reservas activas (no canceladas) del mismo esteticista
  SELECT COUNT(*) INTO v_choques
  FROM reservas r
  JOIN estados_reserva e ON e.id_estado = r.id_estado
  WHERE r.id_esteticista = NEW.id_esteticista
    AND e.nombre_estado NOT IN ('Cancelada', 'No_Show')
    AND NEW.fecha_hora_inicio < r.fecha_hora_fin
    AND NEW.fecha_hora_fin   > r.fecha_hora_inicio;

  IF v_choques > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Horario no disponible: el esteticista ya tiene una reserva en ese rango.';
  END IF;

  -- Choque contra bloqueos de agenda del esteticista
  SELECT COUNT(*) INTO v_choques
  FROM ausencias_bloqueos a
  WHERE a.id_esteticista = NEW.id_esteticista
    AND NEW.fecha_hora_inicio < a.fecha_hora_fin
    AND NEW.fecha_hora_fin   > a.fecha_hora_inicio;

  IF v_choques > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Horario no disponible: el esteticista tiene un bloqueo de agenda en ese rango.';
  END IF;
END$$

CREATE TRIGGER trg_reservas_before_update
BEFORE UPDATE ON reservas
FOR EACH ROW
BEGIN
  DECLARE v_choques INT DEFAULT 0;

  IF (NEW.fecha_hora_inicio <> OLD.fecha_hora_inicio)
     OR (NEW.fecha_hora_fin <> OLD.fecha_hora_fin)
     OR (NEW.id_esteticista <> OLD.id_esteticista) THEN

    SELECT COUNT(*) INTO v_choques
    FROM reservas r
    JOIN estados_reserva e ON e.id_estado = r.id_estado
    WHERE r.id_esteticista = NEW.id_esteticista
      AND r.id_reserva <> NEW.id_reserva
      AND e.nombre_estado NOT IN ('Cancelada', 'No_Show')
      AND NEW.fecha_hora_inicio < r.fecha_hora_fin
      AND NEW.fecha_hora_fin   > r.fecha_hora_inicio;

    IF v_choques > 0 THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Horario no disponible: choque con otra reserva activa del esteticista.';
    END IF;

    SELECT COUNT(*) INTO v_choques
    FROM ausencias_bloqueos a
    WHERE a.id_esteticista = NEW.id_esteticista
      AND NEW.fecha_hora_inicio < a.fecha_hora_fin
      AND NEW.fecha_hora_fin   > a.fecha_hora_inicio;

    IF v_choques > 0 THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Horario no disponible: choque con un bloqueo de agenda del esteticista.';
    END IF;
  END IF;
END$$

-- Registra automaticamente cada cambio de estado en el historial
CREATE TRIGGER trg_reservas_after_update_historial
AFTER UPDATE ON reservas
FOR EACH ROW
BEGIN
  IF NEW.id_estado <> OLD.id_estado THEN
    INSERT INTO historial_estados
      (id_reserva, id_estado_anterior, id_estado_nuevo, id_usuario_modifica, fecha_cambio)
    VALUES
      (NEW.id_reserva, OLD.id_estado, NEW.id_estado, NEW.id_cliente, NOW());
  END IF;
END$$

-- Evita bloqueo que choque con reserva activa
CREATE TRIGGER trg_ausencias_before_insert
BEFORE INSERT ON ausencias_bloqueos
FOR EACH ROW
BEGIN
  DECLARE v_choques INT DEFAULT 0;

  SELECT COUNT(*) INTO v_choques
  FROM reservas r
  JOIN estados_reserva e ON e.id_estado = r.id_estado
  WHERE r.id_esteticista = NEW.id_esteticista
    AND e.nombre_estado NOT IN ('Cancelada', 'No_Show')
    AND NEW.fecha_hora_inicio < r.fecha_hora_fin
    AND NEW.fecha_hora_fin   > r.fecha_hora_inicio;

  IF v_choques > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'No se puede bloquear: ya existe una reserva activa del esteticista en ese rango.';
  END IF;
END$$

DELIMITER ;

-- ============================================================
-- 11. VISTA: agenda del dia por esteticista
-- ============================================================
CREATE OR REPLACE VIEW vw_agenda_dia AS
SELECT
  r.id_reserva,
  r.id_esteticista,
  u.nombre        AS nombre_esteticista,
  r.fecha_hora_inicio,
  r.fecha_hora_fin,
  s.nombre_servicio,
  e.nombre_estado,
  c.nombre        AS nombre_cliente
FROM reservas r
JOIN usuarios u ON u.id_usuario = r.id_esteticista
JOIN usuarios c ON c.id_usuario = r.id_cliente
JOIN servicios s ON s.id_servicio = r.id_servicio
JOIN estados_reserva e ON e.id_estado = r.id_estado;

-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================
