-- Reparación para MariaDB: las tablas ya fueron renombradas al español,
-- pero las columnas todavía están en inglés. Conserva datos, índices y claves foráneas.
-- Ejecutar UNA sola vez y solo cuando las tablas ya se llamen en español.
USE `control_acceso_db`;

ALTER TABLE `estudiantes`
    CHANGE COLUMN `employee_code` `codigo_estudiante` VARCHAR(20) NOT NULL,
    CHANGE COLUMN `first_name` `nombres` VARCHAR(100) NOT NULL,
    CHANGE COLUMN `last_name` `apellidos` VARCHAR(100) NOT NULL,
    CHANGE COLUMN `age` `edad` TINYINT UNSIGNED NULL,
    CHANGE COLUMN `sex` `sexo` ENUM('masculino','femenino','otro') NULL,
    CHANGE COLUMN `email` `correo` VARCHAR(100) NULL,
    CHANGE COLUMN `password` `contrasena` VARCHAR(255) NULL,
    CHANGE COLUMN `department_id` `especialidad_id` INT NULL,
    CHANGE COLUMN `cycle_id` `ciclo_id` TINYINT UNSIGNED NULL,
    CHANGE COLUMN `semester_id` `semestre_id` TINYINT UNSIGNED NULL,
    CHANGE COLUMN `legacy_cycle` `ciclo_legado` VARCHAR(100) NULL,
    CHANGE COLUMN `shift` `turno` ENUM('manana','tarde') NOT NULL DEFAULT 'manana',
    CHANGE COLUMN `photo_path` `ruta_foto` VARCHAR(255) NULL DEFAULT 'default.png',
    CHANGE COLUMN `status` `estado` ENUM('activo','inactivo') NULL DEFAULT 'activo',
    CHANGE COLUMN `created_at` `creado_en` DATETIME NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `especialidades`
    CHANGE COLUMN `name` `nombre` VARCHAR(100) NOT NULL,
    CHANGE COLUMN `status` `estado` ENUM('activo','inactivo') NULL DEFAULT 'activo';
ALTER TABLE `ciclos` CHANGE COLUMN `name` `nombre` VARCHAR(20) NOT NULL;
ALTER TABLE `semestres` CHANGE COLUMN `name` `nombre` VARCHAR(20) NOT NULL;

ALTER TABLE `registros_asistencia`
    CHANGE COLUMN `employee_id` `estudiante_id` INT NOT NULL,
    CHANGE COLUMN `date_log` `fecha` DATE NOT NULL,
    CHANGE COLUMN `check_in_time` `hora_entrada` TIME NULL,
    CHANGE COLUMN `check_out_time` `hora_salida` TIME NULL,
    CHANGE COLUMN `total_hours` `horas_totales` DECIMAL(5,2) NULL,
    CHANGE COLUMN `status` `estado` ENUM('a_tiempo','tarde') NULL DEFAULT 'a_tiempo';

ALTER TABLE `usuarios`
    CHANGE COLUMN `username` `nombre_usuario` VARCHAR(50) NOT NULL,
    CHANGE COLUMN `password` `contrasena` VARCHAR(255) NOT NULL,
    CHANGE COLUMN `role` `rol` ENUM('admin','guardia') NULL DEFAULT 'guardia',
    CHANGE COLUMN `created_at` `creado_en` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    CHANGE COLUMN `status` `estado` ENUM('activo','inactivo') NULL DEFAULT 'activo';

ALTER TABLE `visitantes`
    CHANGE COLUMN `full_name` `nombre_completo` VARCHAR(150) NOT NULL,
    CHANGE COLUMN `company` `empresa` VARCHAR(100) NULL,
    CHANGE COLUMN `phone` `telefono` VARCHAR(20) NULL,
    CHANGE COLUMN `is_banned` `prohibido` TINYINT(1) NULL DEFAULT 0;

ALTER TABLE `registros_visitantes`
    CHANGE COLUMN `visitor_id` `visitante_id` INT NOT NULL,
    CHANGE COLUMN `employee_to_visit_id` `estudiante_a_visitar_id` INT NULL,
    CHANGE COLUMN `reason` `motivo` TEXT NULL,
    CHANGE COLUMN `check_in` `hora_entrada` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    CHANGE COLUMN `check_out` `hora_salida` DATETIME NULL;

ALTER TABLE `incidencias`
    CHANGE COLUMN `title` `titulo` VARCHAR(150) NOT NULL,
    CHANGE COLUMN `description` `descripcion` TEXT NULL,
    CHANGE COLUMN `severity` `gravedad` ENUM('baja','media','alta') NULL DEFAULT 'baja',
    CHANGE COLUMN `created_by` `creado_por` INT NULL,
    CHANGE COLUMN `created_at` `creado_en` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    CHANGE COLUMN `attachment` `adjunto` VARCHAR(255) NULL;

ALTER TABLE `bitacora_auditoria`
    CHANGE COLUMN `actor_user_id` `usuario_actor_id` INT NULL,
    CHANGE COLUMN `actor_employee_id` `estudiante_actor_id` INT NULL,
    CHANGE COLUMN `actor_type` `tipo_actor` VARCHAR(20) NOT NULL DEFAULT 'sistema',
    CHANGE COLUMN `actor_name` `nombre_actor` VARCHAR(150) NOT NULL,
    CHANGE COLUMN `action` `accion` VARCHAR(80) NOT NULL,
    CHANGE COLUMN `entity` `entidad` VARCHAR(80) NOT NULL,
    CHANGE COLUMN `entity_id` `id_entidad` INT NULL,
    CHANGE COLUMN `description` `descripcion` VARCHAR(500) NOT NULL,
    CHANGE COLUMN `details_json` `detalles_json` LONGTEXT NULL,
    CHANGE COLUMN `ip_address` `direccion_ip` VARCHAR(45) NULL,
    CHANGE COLUMN `user_agent` `agente_usuario` VARCHAR(255) NULL,
    CHANGE COLUMN `created_at` `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `configuracion`
    CHANGE COLUMN `setting_name` `nombre_configuracion` VARCHAR(50) NOT NULL,
    CHANGE COLUMN `setting_value` `valor_configuracion` VARCHAR(255) NOT NULL;
