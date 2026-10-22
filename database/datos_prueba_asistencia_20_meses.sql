-- 20 registros ligeros para probar la planilla por meses.
-- Fechas: enero a octubre y agosto a diciembre de 2026.
-- A = asistencia puntual, T = tardanza.
-- Usa estudiantes EST-2026 existentes y solo reemplaza estas 20 fechas exactas.

USE `control_acceso_db`;

CREATE TEMPORARY TABLE `tmp_asistencia_20_meses` (
    `codigo` VARCHAR(20) NOT NULL,
    `fecha` DATE NOT NULL,
    `hora_entrada` TIME NOT NULL,
    `hora_salida` TIME NOT NULL,
    `estado` ENUM('a_tiempo','tarde') NOT NULL,
    PRIMARY KEY (`codigo`, `fecha`)
) ENGINE=MEMORY;

INSERT INTO `tmp_asistencia_20_meses`
    (`codigo`, `fecha`, `hora_entrada`, `hora_salida`, `estado`)
VALUES
    ('EST-2026-0001','2026-01-12','13:55:00','19:00:00','a_tiempo'),
    ('EST-2026-0002','2026-02-10','14:16:00','19:00:00','tarde'),
    ('EST-2026-0003','2026-03-09','13:58:00','19:00:00','a_tiempo'),
    ('EST-2026-0004','2026-04-14','14:12:00','19:00:00','tarde'),
    ('EST-2026-0005','2026-05-11','13:56:00','19:00:00','a_tiempo'),
    ('EST-2026-0006','2026-06-15','14:18:00','19:00:00','tarde'),
    ('EST-2026-0007','2026-07-13','13:57:00','19:00:00','a_tiempo'),
    ('EST-2026-0008','2026-08-10','14:10:00','19:00:00','tarde'),
    ('EST-2026-0009','2026-09-14','13:54:00','19:00:00','a_tiempo'),
    ('EST-2026-0010','2026-10-12','14:14:00','19:00:00','tarde'),
    ('EST-2026-0011','2026-08-17','13:55:00','19:00:00','a_tiempo'),
    ('EST-2026-0012','2026-08-24','14:15:00','19:00:00','tarde'),
    ('EST-2026-0013','2026-09-07','13:56:00','19:00:00','a_tiempo'),
    ('EST-2026-0001','2026-09-21','14:11:00','19:00:00','tarde'),
    ('EST-2026-0002','2026-10-05','13:57:00','19:00:00','a_tiempo'),
    ('EST-2026-0003','2026-10-19','14:17:00','19:00:00','tarde'),
    ('EST-2026-0004','2026-11-09','13:55:00','19:00:00','a_tiempo'),
    ('EST-2026-0005','2026-11-23','14:13:00','19:00:00','tarde'),
    ('EST-2026-0006','2026-12-07','13:58:00','19:00:00','a_tiempo'),
    ('EST-2026-0007','2026-12-21','14:19:00','19:00:00','tarde');

-- Primero borra movimientos relacionados y luego solo las 20 asistencias de prueba.
DELETE m
FROM `movimientos_asistencia` m
INNER JOIN `registros_asistencia` a ON a.`id` = m.`registro_asistencia_id`
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
INNER JOIN `tmp_asistencia_20_meses` t ON t.`codigo` = e.`codigo_estudiante` AND t.`fecha` = a.`fecha`;

DELETE a
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
INNER JOIN `tmp_asistencia_20_meses` t ON t.`codigo` = e.`codigo_estudiante` AND t.`fecha` = a.`fecha`;

INSERT INTO `registros_asistencia`
    (`estudiante_id`, `fecha`, `hora_entrada`, `hora_salida`, `horas_totales`, `estado`)
SELECT e.`id`, t.`fecha`, t.`hora_entrada`, t.`hora_salida`,
       ROUND(TIME_TO_SEC(TIMEDIFF(t.`hora_salida`, t.`hora_entrada`)) / 3600, 2),
       t.`estado`
FROM `tmp_asistencia_20_meses` t
INNER JOIN `estudiantes` e ON e.`codigo_estudiante` = t.`codigo`
WHERE e.`estado` = 'activo';

DROP TEMPORARY TABLE IF EXISTS `tmp_asistencia_20_meses`;

-- Verificación: debe mostrar 20 registros; estado tarde equivale a T.
SELECT a.`fecha`, e.`codigo_estudiante` AS codigo,
       CONCAT(e.`apellidos`, ', ', e.`nombres`) AS estudiante,
       CASE WHEN a.`estado` = 'tarde' THEN 'T' ELSE 'A' END AS marca
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE (e.`codigo_estudiante`, a.`fecha`) IN (
    ('EST-2026-0001','2026-01-12'),('EST-2026-0002','2026-02-10'),
    ('EST-2026-0003','2026-03-09'),('EST-2026-0004','2026-04-14'),
    ('EST-2026-0005','2026-05-11'),('EST-2026-0006','2026-06-15'),
    ('EST-2026-0007','2026-07-13'),('EST-2026-0008','2026-08-10'),
    ('EST-2026-0009','2026-09-14'),('EST-2026-0010','2026-10-12'),
    ('EST-2026-0011','2026-08-17'),('EST-2026-0012','2026-08-24'),
    ('EST-2026-0013','2026-09-07'),('EST-2026-0001','2026-09-21'),
    ('EST-2026-0002','2026-10-05'),('EST-2026-0003','2026-10-19'),
    ('EST-2026-0004','2026-11-09'),('EST-2026-0005','2026-11-23'),
    ('EST-2026-0006','2026-12-07'),('EST-2026-0007','2026-12-21')
)
ORDER BY a.`fecha`, e.`codigo_estudiante`;
