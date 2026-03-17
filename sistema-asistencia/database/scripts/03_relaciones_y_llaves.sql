-- ========================================================
-- SISTEMA DE REGISTRO DE ASISTENCIA
-- Archivo: 03_relaciones_y_llaves.sql
-- Descripción: Adición de llaves foráneas y restricciones
--              de integridad referencial.
-- ========================================================

USE `asistencia_db`;

-- ========================================================
-- RELACIÓN: empleados -> usuarios
-- Descripción: Un empleado está ligado a un usuario.
--              Si un usuario se elimina (soft delete), el empleado
--              debe serlo también.
-- ========================================================
ALTER TABLE `empleados`
ADD CONSTRAINT `fk_empleados_id_usuario`
FOREIGN KEY (`id_usuario`)
REFERENCES `usuarios` (`id`)
ON DELETE CASCADE  -- Si se elimina el usuario, se elimina el registro de empleado.
ON UPDATE CASCADE; -- Si cambia el ID del usuario (raro), se actualiza aquí.

-- ========================================================
-- RELACIÓN: empleados -> departamentos
-- Descripción: Un empleado pertenece a un departamento.
--              Si un departamento se elimina, se debe manejarel caso.
--              RESTRICT evita eliminar deptos con empleados activos.
-- ========================================================
ALTER TABLE `empleados`
ADD CONSTRAINT `fk_empleados_id_departamento`
FOREIGN KEY (`id_departamento`)
REFERENCES `departamentos` (`id`)
ON DELETE RESTRICT  -- No permite eliminar un departamento que tenga empleados asignados.
ON UPDATE CASCADE;

-- ========================================================
-- RELACIÓN: asistencias -> empleados
-- Descripción: Un registro de asistencia pertenece a un empleado.
--              Si un empleado se elimina (soft delete), sus asistencias
--              pueden quedar por historial (SET NULL no es buena idea aquí).
--              CASCADE es más limpio si el empleado se va definitivamente.
-- ========================================================
ALTER TABLE `asistencias`
ADD CONSTRAINT `fk_asistencias_id_empleado`
FOREIGN KEY (`id_empleado`)
REFERENCES `empleados` (`id`)
ON DELETE CASCADE  -- Si el empleado se elimina, sus registros de asistencia también.
ON UPDATE CASCADE;
