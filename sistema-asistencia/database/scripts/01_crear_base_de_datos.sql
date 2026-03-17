-- ========================================================
-- SISTEMA DE REGISTRO DE ASISTENCIA
-- Archivo: 01_crear_base_de_datos.sql
-- Descripción: Crea la base de datos.
-- ========================================================

-- Eliminar la base de datos si existe (para entornos de desarrollo/prueba)
DROP DATABASE IF EXISTS `asistencia_db`;

-- Crear la base de datos con el cotejamiento adecuado para español
CREATE DATABASE `asistencia_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Seleccionar la base de datos para los siguientes scripts
USE `asistencia_db`;
