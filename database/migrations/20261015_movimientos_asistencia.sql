-- Registra entradas, salidas temporales/finales y reingresos durante el mismo día.
USE `control_acceso_db`;

CREATE TABLE IF NOT EXISTS `movimientos_asistencia` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `registro_asistencia_id` INT NOT NULL,
    `estudiante_id` INT NOT NULL,
    `tipo` ENUM('entrada','salida_temporal','salida_final','reingreso') NOT NULL,
    `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `motivo` VARCHAR(300) DEFAULT NULL,
    `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_movimientos_asistencia_registro` (`registro_asistencia_id`,`fecha_hora`),
    KEY `idx_movimientos_asistencia_estudiante` (`estudiante_id`,`fecha_hora`),
    CONSTRAINT `fk_movimientos_registro_asistencia`
        FOREIGN KEY (`registro_asistencia_id`) REFERENCES `registros_asistencia` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_movimientos_estudiante`
        FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `configuracion` (`nombre_configuracion`, `valor_configuracion`) VALUES
    ('morning_entry_time', '08:00'),
    ('morning_exit_time', '13:00'),
    ('afternoon_entry_time', '14:00'),
    ('afternoon_exit_time', '19:00')
ON DUPLICATE KEY UPDATE `valor_configuracion` = VALUES(`valor_configuracion`);
