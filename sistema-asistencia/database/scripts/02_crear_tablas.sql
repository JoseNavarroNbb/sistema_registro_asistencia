-- ========================================================
-- SISTEMA DE REGISTRO DE ASISTENCIA
-- Archivo: 02_crear_tablas.sql
-- Descripción: Definición de todas las tablas (DDL).
-- ========================================================

USE `asistencia_db`;

-- ========================================================
-- TABLA: usuarios
-- Descripción: Almacena la información de autenticación y
--              datos básicos de todos los usuarios del sistema.
-- ========================================================
CREATE TABLE `usuarios` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT COMMENT 'Identificador único del usuario (PK)',
    `nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre(s) del usuario',
    `apellido` VARCHAR(100) NOT NULL COMMENT 'Apellido(s) del usuario',
    `correo` VARCHAR(150) NOT NULL COMMENT 'Correo electrónico, único y usado para login',
    `contrasena` VARCHAR(255) NOT NULL COMMENT 'Hash de la contraseña (usando Bcrypt de Laravel)',
    `rol` ENUM('admin', 'empleado') NOT NULL DEFAULT 'empleado' COMMENT 'Rol del User: administrador o empleado',
    `estado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Estado del User: 1 = activo, 0 = inactivo',
    `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de creación del registro (Laravel)',
    `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de última modificación del registro (Laravel)',
    `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de eliminación lógica (Soft Deletes)',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `usuarios_correo_unique` (`correo` ASC) VISIBLE
) ENGINE = InnoDB COMMENT = 'Catálogo de usuarios del sistema';

-- ========================================================
-- TABLA: departamentos
-- Descripción: Catálogo de departamentos de la empresa.
-- ========================================================
CREATE TABLE `departamentos` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT COMMENT 'Identificador único del departamento (PK)',
    `nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre del departamento (ej. "Ventas", "Tecnología")',
    `descripcion` TEXT NULL COMMENT 'Descripción opcional del departamento',
    `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de creación del registro',
    `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de última modificación del registro',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `departamentos_nombre_unique` (`nombre` ASC) VISIBLE
) ENGINE = InnoDB COMMENT = 'Catálogo de departamentos de la organización';

-- ========================================================
-- TABLA: empleados
-- Descripción: Información laboral específica de los usuarios
--              que tienen rol 'empleado'. Extiende la tabla 'usuarios'.
-- ========================================================
CREATE TABLE `empleados` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT COMMENT 'Identificador único del empleado (PK)',
    `id_usuario` BIGINT UNSIGNED NOT NULL COMMENT 'ID del usuario asociado (FK a usuarios)',
    `id_departamento` BIGINT UNSIGNED NOT NULL COMMENT 'ID del departamento al que pertenece (FK a departamentos)',
    `cargo` VARCHAR(100) NOT NULL COMMENT 'Puesto o cargo del empleado (ej. "Desarrollador Senior")',
    `codigo_empleado` VARCHAR(20) NOT NULL COMMENT 'Código interno único del empleado (ej. EMP-001)',
    `fecha_ingreso` DATE NOT NULL COMMENT 'Fecha en la que el empleado ingresó a la empresa',
    `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de creación del registro',
    `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de última modificación del registro',
    `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de eliminación lógica (Soft Deletes)',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `empleados_codigo_empleado_unique` (`codigo_empleado` ASC) VISIBLE,
    INDEX `empleados_id_usuario_foreign_idx` (`id_usuario` ASC) VISIBLE,
    INDEX `empleados_id_departamento_foreign_idx` (`id_departamento` ASC) VISIBLE
) ENGINE = InnoDB COMMENT = 'Datos laborales de los empleados';

-- ========================================================
-- TABLA: asistencias
-- Descripción: Registro de las marcaciones de entrada y salida
--              de los empleados.
-- ========================================================
CREATE TABLE `asistencias` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT COMMENT 'Identificador único del registro de asistencia (PK)',
    `id_empleado` BIGINT UNSIGNED NOT NULL COMMENT 'ID del empleado que marca (FK a empleados)',
    `fecha` DATE NOT NULL COMMENT 'Fecha de la marcación',
    `hora_entrada` TIME NOT NULL COMMENT 'Hora de marcación de entrada',
    `hora_salida` TIME NULL DEFAULT NULL COMMENT 'Hora de marcación de salida (puede ser nula si aún no sale)',
    `total_horas` DECIMAL(5,2) UNSIGNED NULL DEFAULT NULL COMMENT 'Total de horas trabajadas (calculado al marcar salida)',
    `observacion` TEXT NULL COMMENT 'Campo para notas u observaciones (ej. "Permiso para ir al médico")',
    `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de creación del registro',
    `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de última modificación del registro',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `asistencias_empleado_fecha_unique` (`id_empleado` ASC, `fecha` ASC) COMMENT 'Un empleado solo puede tener un registro por día',
    INDEX `asistencias_id_empleado_foreign_idx` (`id_empleado` ASC) VISIBLE,
    INDEX `asistencias_fecha_index` (`fecha` ASC) COMMENT 'Índice para búsquedas por rango de fechas'
) ENGINE = InnoDB COMMENT = 'Registro diario de marcaciones de entrada y salida';


-- ========================================================
-- TABLA: password_reset_tokens
-- Descripción: Almacena tokens temporales para recuperación
--              de contraseñas por correo electrónico.
-- ========================================================
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `email` VARCHAR(150) NOT NULL COMMENT 'Correo electrónico del usuario',
    `token` VARCHAR(255) NOT NULL COMMENT 'Token único para restablecer contraseña',
    `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de creación del token',
    PRIMARY KEY (`email`),
    INDEX `password_reset_tokens_token_index` (`token`)
) ENGINE = InnoDB COMMENT = 'Tokens para recuperación de contraseñas';



-- ========================================================
-- TABLA: personal_access_tokens
-- Descripción: Almacena los tokens de autenticación de Sanctum
-- ========================================================
CREATE TABLE `personal_access_tokens` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tokenable_type` VARCHAR(255) NOT NULL COMMENT 'Tipo de modelo que puede tener tokens (ej. App\Models\User)',
    `tokenable_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID del modelo asociado',
    `name` VARCHAR(255) NOT NULL COMMENT 'Nombre del token (ej. auth_token)',
    `token` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Hash único del token',
    `abilities` TEXT NULL COMMENT 'Habilidades/permissions del token',
    `last_used_at` TIMESTAMP NULL COMMENT 'Última fecha de uso',
    `expires_at` TIMESTAMP NULL COMMENT 'Fecha de expiración',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`)
) ENGINE = InnoDB COMMENT = 'Tokens de autenticación personal (Laravel Sanctum)';