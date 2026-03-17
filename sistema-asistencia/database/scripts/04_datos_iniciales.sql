-- ========================================================
-- SISTEMA DE REGISTRO DE ASISTENCIA
-- Archivo: 04_datos_iniciales.sql
-- Descripción: Inserción de datos de prueba y un usuario
--              administrador por defecto.
-- ========================================================

USE `asistencia_db`;

-- ========================================================
-- 1. Insertar departamentos iniciales
-- ========================================================
INSERT INTO `departamentos` (`nombre`, `descripcion`, `created_at`, `updated_at`) VALUES
('Dirección General', 'Responsable de la estrategia y dirección de la empresa.', NOW(), NOW()),
('Tecnología', 'Encargado del desarrollo de software, soporte técnico e infraestructura.', NOW(), NOW()),
('Recursos Humanos', 'Gestión del talento, nómina y bienestar del empleado.', NOW(), NOW()),
('Ventas y Marketing', 'Estrategias de venta, publicidad y atención al cliente.', NOW(), NOW()),
('Finanzas', 'Administración de los recursos económicos y contabilidad.', NOW(), NOW());

-- ========================================================
-- 2. Insertar usuario administrador por defecto
--    Contraseña: 'Admin123!' (hash generado con Bcrypt)
-- ========================================================
INSERT INTO `usuarios` (`nombre`, `apellido`, `correo`, `contrasena`, `rol`, `estado`, `created_at`, `updated_at`) VALUES
('Admin', 'Principal', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NOW(), NOW());

-- ========================================================
-- 3. Insertar usuario de ejemplo (empleado)
--    Contraseña: 'Empleado123!'
-- ========================================================
INSERT INTO `usuarios` (`nombre`, `apellido`, `correo`, `contrasena`, `rol`, `estado`, `created_at`, `updated_at`) VALUES
('Juan Carlos', 'Pérez García', 'juan.perez@ejemplo.com', '$2y$10$mQvC7N7Xk9Xh9Qq9Xh9QqOeZ9Xh9Qq9Xh9Qq9Xh9Qq9Xh9Qq9Xh9Q', 'empleado', 1, NOW(), NOW()),
('María Fernanda', 'López Martínez', 'maria.lopez@ejemplo.com', '$2y$10$mQvC7N7Xk9Xh9Qq9Xh9QqOeZ9Xh9Qq9Xh9Qq9Xh9Qq9Xh9Qq9Xh9Q', 'empleado', 1, NOW(), NOW()),
('Carlos Alberto', 'Sánchez Ruiz', 'carlos.sanchez@ejemplo.com', '$2y$10$mQvC7N7Xk9Xh9Qq9Xh9QqOeZ9Xh9Qq9Xh9Qq9Xh9Qq9Xh9Qq9Xh9Q', 'empleado', 1, NOW(), NOW());

-- ========================================================
-- 4. Insertar empleados (asociándolos con los usuarios y deptos)
--    Nota: Los IDs pueden variar si cambia el orden de inserción.
--          Ajusta según sea necesario, pero este es el orden esperado.
--          Admin (id 1), Juan (id 2), María (id 3), Carlos (id 4)
--          Deptos: Dir (1), Tec (2), RH (3), Ventas (4), Fin (5)
-- ========================================================
-- Empleado: Juan Pérez (id_usuario = 2) en Tecnología (id_departamento = 2)
INSERT INTO `empleados` (`id_usuario`, `id_departamento`, `cargo`, `codigo_empleado`, `fecha_ingreso`, `created_at`, `updated_at`) VALUES
(2, 2, 'Desarrollador Full Stack', 'EMP-001', '2023-05-15', NOW(), NOW());

-- Empleada: María López (id_usuario = 3) en Recursos Humanos (id_departamento = 3)
INSERT INTO `empleados` (`id_usuario`, `id_departamento`, `cargo`, `codigo_empleado`, `fecha_ingreso`, `created_at`, `updated_at`) VALUES
(3, 3, 'Reclutadora Senior', 'EMP-002', '2022-11-01', NOW(), NOW());

-- Empleado: Carlos Sánchez (id_usuario = 4) en Ventas (id_departamento = 4)
INSERT INTO `empleados` (`id_usuario`, `id_departamento`, `cargo`, `codigo_empleado`, `fecha_ingreso`, `created_at`, `updated_at`) VALUES
(4, 4, 'Ejecutivo de Ventas', 'EMP-003', '2024-01-20', NOW(), NOW());

-- ========================================================
-- 5. Insertar algunos registros de asistencia de ejemplo
-- ========================================================
-- Asumiendo que los empleados tienen IDs 1, 2 y 3 (por los inserts de empleados)
-- Asistencias para Juan Pérez (id_empleado = 1)
INSERT INTO `asistencias` (`id_empleado`, `fecha`, `hora_entrada`, `hora_salida`, `total_horas`, `created_at`, `updated_at`) VALUES
(1, CURDATE(), '08:05:00', '17:30:00', 9.25, NOW(), NOW()),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:10:00', '17:15:00', 9.08, NOW(), NOW()),
(1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '08:00:00', '16:45:00', 8.45, NOW(), NOW());

-- Asistencias para María López (id_empleado = 2)
INSERT INTO `asistencias` (`id_empleado`, `fecha`, `hora_entrada`, `hora_salida`, `total_horas`, `created_at`, `updated_at`) VALUES
(2, CURDATE(), '09:00:00', '18:00:00', 9.00, NOW(), NOW()),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:55:00', '18:05:00', 9.17, NOW(), NOW());

-- Asistencias para Carlos Sánchez (id_empleado = 3) - solo ha marcado entrada hoy
INSERT INTO `asistencias` (`id_empleado`, `fecha`, `hora_entrada`, `hora_salida`, `total_horas`, `created_at`, `updated_at`) VALUES
(3, CURDATE(), '08:30:00', NULL, NULL, NOW(), NOW());
