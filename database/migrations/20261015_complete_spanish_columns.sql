-- Recuperacion para la migracion parcial donde las tablas ya estan en espanol,
-- pero sus columnas siguen en ingles. No renombra tablas ni modifica los datos.
-- Hacer un respaldo antes de ejecutar. Ejecutar una sola vez.
USE `control_acceso_db`;

ALTER TABLE `estudiantes`
    RENAME COLUMN `employee_code` TO `codigo_estudiante`,
    RENAME COLUMN `first_name` TO `nombres`,
    RENAME COLUMN `last_name` TO `apellidos`,
    RENAME COLUMN `age` TO `edad`,
    RENAME COLUMN `sex` TO `sexo`,
    RENAME COLUMN `email` TO `correo`,
    RENAME COLUMN `password` TO `contrasena`,
    RENAME COLUMN `department_id` TO `especialidad_id`,
    RENAME COLUMN `cycle_id` TO `ciclo_id`,
    RENAME COLUMN `semester_id` TO `semestre_id`,
    RENAME COLUMN `legacy_cycle` TO `ciclo_legado`,
    RENAME COLUMN `shift` TO `turno`,
    RENAME COLUMN `photo_path` TO `ruta_foto`,
    RENAME COLUMN `status` TO `estado`,
    RENAME COLUMN `created_at` TO `creado_en`;

ALTER TABLE `especialidades`
    RENAME COLUMN `name` TO `nombre`,
    RENAME COLUMN `status` TO `estado`;

ALTER TABLE `ciclos`
    RENAME COLUMN `name` TO `nombre`;

ALTER TABLE `semestres`
    RENAME COLUMN `name` TO `nombre`;

ALTER TABLE `registros_asistencia`
    RENAME COLUMN `employee_id` TO `estudiante_id`,
    RENAME COLUMN `date_log` TO `fecha`,
    RENAME COLUMN `check_in_time` TO `hora_entrada`,
    RENAME COLUMN `check_out_time` TO `hora_salida`,
    RENAME COLUMN `total_hours` TO `horas_totales`,
    RENAME COLUMN `status` TO `estado`;

ALTER TABLE `usuarios`
    RENAME COLUMN `username` TO `nombre_usuario`,
    RENAME COLUMN `password` TO `contrasena`,
    RENAME COLUMN `role` TO `rol`,
    RENAME COLUMN `created_at` TO `creado_en`,
    RENAME COLUMN `status` TO `estado`;

ALTER TABLE `visitantes`
    RENAME COLUMN `full_name` TO `nombre_completo`,
    RENAME COLUMN `company` TO `empresa`,
    RENAME COLUMN `phone` TO `telefono`,
    RENAME COLUMN `is_banned` TO `prohibido`;

ALTER TABLE `registros_visitantes`
    RENAME COLUMN `visitor_id` TO `visitante_id`,
    RENAME COLUMN `employee_to_visit_id` TO `estudiante_a_visitar_id`,
    RENAME COLUMN `reason` TO `motivo`,
    RENAME COLUMN `check_in` TO `hora_entrada`,
    RENAME COLUMN `check_out` TO `hora_salida`;

ALTER TABLE `incidencias`
    RENAME COLUMN `title` TO `titulo`,
    RENAME COLUMN `description` TO `descripcion`,
    RENAME COLUMN `severity` TO `gravedad`,
    RENAME COLUMN `created_by` TO `creado_por`,
    RENAME COLUMN `created_at` TO `creado_en`,
    RENAME COLUMN `attachment` TO `adjunto`;

ALTER TABLE `bitacora_auditoria`
    RENAME COLUMN `actor_user_id` TO `usuario_actor_id`,
    RENAME COLUMN `actor_employee_id` TO `estudiante_actor_id`,
    RENAME COLUMN `actor_type` TO `tipo_actor`,
    RENAME COLUMN `actor_name` TO `nombre_actor`,
    RENAME COLUMN `action` TO `accion`,
    RENAME COLUMN `entity` TO `entidad`,
    RENAME COLUMN `entity_id` TO `id_entidad`,
    RENAME COLUMN `description` TO `descripcion`,
    RENAME COLUMN `details_json` TO `detalles_json`,
    RENAME COLUMN `ip_address` TO `direccion_ip`,
    RENAME COLUMN `user_agent` TO `agente_usuario`,
    RENAME COLUMN `created_at` TO `creado_en`;

ALTER TABLE `configuracion`
    RENAME COLUMN `setting_name` TO `nombre_configuracion`,
    RENAME COLUMN `setting_value` TO `valor_configuracion`;
