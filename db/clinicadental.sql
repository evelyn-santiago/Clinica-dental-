-- ============================================================
--  BASE DE DATOS: Clínica Dental Sonrisa Perfecta
--  Compatible con MySQL Workbench
-- ============================================================

CREATE DATABASE IF NOT EXISTS clinicadental
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_spanish_ci;

USE clinicadental;

-- Limpiar tablas si existen para asegurar estructura correcta
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS citas;
DROP TABLE IF EXISTS pacientes;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- TABLA: usuarios (para el login)
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario     VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    nombre      VARCHAR(100) NOT NULL,
    rol         ENUM('admin','odontologo','recepcionista') NOT NULL DEFAULT 'odontologo',
    avatar_url  VARCHAR(255) DEFAULT NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- TABLA: pacientes
-- ------------------------------------------------------------
CREATE TABLE pacientes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    apellido        VARCHAR(100) NOT NULL,
    telefono        VARCHAR(20)  DEFAULT NULL,
    email           VARCHAR(100) DEFAULT NULL,
    fecha_nacimiento DATE         DEFAULT NULL,
    direccion       VARCHAR(255) DEFAULT NULL,
    notas           TEXT         DEFAULT NULL,
    creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- TABLA: citas
-- ------------------------------------------------------------
CREATE TABLE citas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id     INT          NOT NULL,
    odontologo_id   INT          NOT NULL,
    fecha           DATE         NOT NULL,
    hora            TIME         NOT NULL,
    procedimiento   VARCHAR(100) NOT NULL,
    estado          ENUM('pendiente','confirmada','completada','cancelada') NOT NULL DEFAULT 'pendiente',
    notas           TEXT         DEFAULT NULL,
    creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id)   REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (odontologo_id) REFERENCES usuarios(id)  ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- DATOS DE PRUEBA
-- ------------------------------------------------------------

-- Usuarios (password: 1234 → en producción usar bcrypt)
INSERT INTO usuarios (usuario, password, nombre, rol, avatar_url) VALUES
('naye',   MD5('1234'), 'Dr. Juan Perez',   'odontologo',    'https://randomuser.me/api/portraits/men/32.jpg'),
('admin',  MD5('admin123'), 'Administrador', 'admin',        'https://randomuser.me/api/portraits/men/1.jpg'),
('recep',  MD5('recep123'), 'Laura Gómez',   'recepcionista', 'https://randomuser.me/api/portraits/women/44.jpg');

-- Pacientes
INSERT INTO pacientes (nombre, apellido, telefono, email, fecha_nacimiento) VALUES
('María',  'González', '555-1001', 'maria.gonzalez@email.com',  '1990-05-12'),
('Carlos', 'Rodríguez','555-1002', 'carlos.rodriguez@email.com','1985-08-23'),
('Ana',    'Martínez', '555-1003', 'ana.martinez@email.com',    '1995-03-15'),
('Luis',   'Fernández','555-1004', 'luis.fernandez@email.com',  '1978-11-30'),
('Sofía',  'Ramírez',  '555-1005', 'sofia.ramirez@email.com',   '2000-07-04'),
('Jorge',  'Sánchez',  '555-1006', 'jorge.sanchez@email.com',   '1992-09-18');

-- Citas (odontologo_id = 1 → naye)
INSERT INTO citas (paciente_id, odontologo_id, fecha, hora, procedimiento, estado) VALUES
(1, 1, '2026-04-05', '10:00:00', 'Limpieza Dental', 'confirmada'),
(2, 1, '2026-04-05', '11:30:00', 'Extracción',      'confirmada'),
(3, 1, '2026-04-05', '14:00:00', 'Ortodoncia',      'pendiente'),
(4, 1, '2026-04-03', '09:00:00', 'Blanqueamiento',  'completada'),
(5, 1, '2026-04-02', '10:30:00', 'Revisión',        'completada'),
(6, 1, '2026-04-01', '15:00:00', 'Carilla',         'completada');

-- ------------------------------------------------------------
-- TABLA: facturas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS facturas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    cita_id         INT          NOT NULL,
    monto           DECIMAL(10,2) NOT NULL,
    metodo_pago     ENUM('efectivo','tarjeta','transferencia') NOT NULL DEFAULT 'efectivo',
    estado_pago     ENUM('pagado','pendiente') NOT NULL DEFAULT 'pagado',
    fecha_emision   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE
);

-- Datos de prueba para facturas
INSERT INTO facturas (cita_id, monto, metodo_pago, estado_pago) VALUES
(4, 1500.00, 'tarjeta', 'pagado'),
(5, 500.00,  'efectivo', 'pagado'),
(6, 3500.00, 'transferencia', 'pagado');
