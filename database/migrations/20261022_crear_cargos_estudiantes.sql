-- Módulo de cargos académicos.
-- Crea el catálogo de cargos y relaciona cada estudiante con un cargo.
-- Es seguro ejecutarlo más de una vez en MariaDB/MySQL.

USE `control_acceso_db`;

-- 1. Catálogo administrable de cargos.
CREATE TABLE IF NOT EXISTS `cargos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(80) NOT NULL,
    `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cargos_nombre` (`nombre`),
    KEY `idx_cargos_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Cargos iniciales. No duplica registros si se vuelve a ejecutar.
INSERT INTO `cargos` (`nombre`, `estado`) VALUES
    ('Delegado', 'activo'),
    ('Secretaria', 'activo'),
    ('Subdirector', 'activo'),
    ('Subdelegado', 'activo'),
    ('Alumno', 'activo'),
    ('Docente', 'activo')
ON DUPLICATE KEY UPDATE `estado` = VALUES(`estado`);

-- 3. Nueva columna en estudiantes.
ALTER TABLE `estudiantes`
    ADD COLUMN IF NOT EXISTS `cargo_id` INT UNSIGNED NULL AFTER `semestre_id`;

-- 4. Índice y clave foránea, sin fallar si ya existen.
SET @bd := DATABASE();

SELECT COUNT(*) INTO @indice_existe
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @bd
  AND TABLE_NAME = 'estudiantes'
  AND INDEX_NAME = 'idx_estudiantes_cargo_id';

SET @sql_indice := IF(
    @indice_existe = 0,
    'ALTER TABLE `estudiantes` ADD KEY `idx_estudiantes_cargo_id` (`cargo_id`)',
    'SELECT 1'
);
PREPARE sentencia_indice FROM @sql_indice;
EXECUTE sentencia_indice;
DEALLOCATE PREPARE sentencia_indice;

SELECT COUNT(*) INTO @fk_existe
FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = @bd
  AND TABLE_NAME = 'estudiantes'
  AND CONSTRAINT_NAME = 'fk_estudiantes_cargo';

SET @sql_fk := IF(
    @fk_existe = 0,
    'ALTER TABLE `estudiantes` ADD CONSTRAINT `fk_estudiantes_cargo` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE sentencia_fk FROM @sql_fk;
EXECUTE sentencia_fk;
DEALLOCATE PREPARE sentencia_fk;

-- 5. Los estudiantes existentes quedan registrados inicialmente como Alumno.
UPDATE `estudiantes` e
INNER JOIN `cargos` c ON c.`nombre` = 'Alumno'
SET e.`cargo_id` = c.`id`
WHERE e.`cargo_id` IS NULL;

-- Comprobación final.
SELECT e.`codigo_estudiante`, e.`nombres`, e.`apellidos`, c.`nombre` AS `cargo`
FROM `estudiantes` e
LEFT JOIN `cargos` c ON c.`id` = e.`cargo_id`
ORDER BY e.`id` DESC
LIMIT 20;
