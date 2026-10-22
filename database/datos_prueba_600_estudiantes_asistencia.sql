-- CARGA SEGURA DE PRUEBA: 100 estudiantes y planilla docente.
-- Códigos exclusivos: PRU-REG-0001 a PRU-REG-0100.
-- A = asistió puntual, T = tardanza y F = falta (la falta no se inserta).
--
-- Esta versión NO usa una transacción gigante. Inserta las asistencias
-- por cada mes para no saturar MySQL/XAMPP.
-- Puede ejecutarse otra vez: solo limpia las asistencias PRU-REG-.

USE `control_acceso_db`;

-- 1. Limpieza acotada: nunca toca estudiantes ni asistencias reales.
DELETE m
FROM `movimientos_asistencia` m
INNER JOIN `registros_asistencia` a ON a.`id` = m.`registro_asistencia_id`
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE e.`codigo_estudiante` LIKE 'PRU-REG-%';

DELETE a
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE e.`codigo_estudiante` LIKE 'PRU-REG-%';

-- Si quedó una ejecución anterior de 600 alumnos, se retiran únicamente
-- los códigos de prueba excedentes para que esta versión conserve 100.
DELETE FROM `estudiantes`
WHERE `codigo_estudiante` LIKE 'PRU-REG-%'
  AND `codigo_estudiante` > 'PRU-REG-0100';

-- 2. Crear 100 estudiantes ficticios: volumen seguro para XAMPP.
-- INSERT IGNORE permite ejecutar el archivo otra vez sin actualizarlos ni duplicarlos.
INSERT IGNORE INTO `estudiantes` (
    `codigo_estudiante`, `nombres`, `apellidos`, `dni`, `edad`,
    `sexo`, `correo`, `especialidad_id`, `ciclo_id`, `semestre_id`,
    `turno`, `ruta_foto`, `estado`
)
SELECT
    CONCAT('PRU-REG-', LPAD(n.numero, 4, '0')),
    CONCAT('ESTUDIANTE ', LPAD(n.numero, 4, '0')),
    'PRUEBA DE ASISTENCIA',
    CAST(99000000 + n.numero AS CHAR),
    18 + MOD(n.numero, 8),
    IF(MOD(n.numero, 2) = 0, 'femenino', 'masculino'),
    CONCAT('prueba', n.numero, '@instituto.test'),
    (SELECT `id` FROM `especialidades` WHERE `estado` = 'activo' ORDER BY `id` LIMIT 1),
    (SELECT `id` FROM `ciclos` ORDER BY `id` LIMIT 1),
    (SELECT `id` FROM `semestres` ORDER BY `id` LIMIT 1),
    IF(MOD(n.numero, 2) = 0, 'manana', 'tarde'),
    'default.png',
    'activo'
FROM (
    SELECT u.n + d.n * 10 + c.n * 100 + 1 AS numero
    FROM (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) u
    CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) d
    CROSS JOIN (SELECT 0 n) c
) n
WHERE n.numero <= 100;

-- 3. Fechas de octubre a diciembre de 2026. Se excluyen solo los domingos.
CREATE TEMPORARY TABLE `tmp_fechas_prueba` (
    `fecha` DATE NOT NULL PRIMARY KEY
) ENGINE=MEMORY;

INSERT INTO `tmp_fechas_prueba` (`fecha`)
SELECT DATE_ADD('2026-10-01', INTERVAL n.numero DAY)
FROM (
    SELECT u.n + d.n * 10 AS numero
    FROM (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) u
    CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) d
) n
WHERE n.numero <= 91
  AND DAYOFWEEK(DATE_ADD('2026-10-01', INTERVAL n.numero DAY)) <> 1;

-- 4. Inserciones separadas por mes. Cada bloque crea alrededor de 2,500 filas,
-- en lugar de cargar todo el trimestre en una sola operación.
INSERT INTO `registros_asistencia`
    (`estudiante_id`, `fecha`, `hora_entrada`, `hora_salida`, `horas_totales`, `estado`)
SELECT e.`id`, f.`fecha`,
       CASE WHEN MOD(e.`id` + DAYOFYEAR(f.`fecha`), 5) = 1 AND e.`turno` = 'manana' THEN '08:15:00'
            WHEN MOD(e.`id` + DAYOFYEAR(f.`fecha`), 5) = 1 THEN '14:15:00'
            WHEN e.`turno` = 'manana' THEN '08:00:00' ELSE '14:00:00' END,
       CASE WHEN e.`turno` = 'manana' THEN '13:00:00' ELSE '19:00:00' END,
       CASE WHEN MOD(e.`id` + DAYOFYEAR(f.`fecha`), 5) = 1 THEN 4.75 ELSE 5.00 END,
       CASE WHEN MOD(e.`id` + DAYOFYEAR(f.`fecha`), 5) = 1 THEN 'tarde' ELSE 'a_tiempo' END
FROM `estudiantes` e CROSS JOIN `tmp_fechas_prueba` f
WHERE e.`codigo_estudiante` LIKE 'PRU-REG-%'
  AND f.`fecha` BETWEEN '2026-10-01' AND '2026-10-31'
  AND f.`fecha` <> CURDATE()
  AND MOD(e.`id` + DAYOFYEAR(f.`fecha`), 9) <> 0;

INSERT INTO `registros_asistencia`
    (`estudiante_id`, `fecha`, `hora_entrada`, `hora_salida`, `horas_totales`, `estado`)
SELECT e.`id`, f.`fecha`,
       CASE WHEN MOD(e.`id` + DAYOFYEAR(f.`fecha`), 5) = 1 AND e.`turno` = 'manana' THEN '08:15:00'
            WHEN MOD(e.`id` + DAYOFYEAR(f.`fecha`), 5) = 1 THEN '14:15:00'
            WHEN e.`turno` = 'manana' THEN '08:00:00' ELSE '14:00:00' END,
       CASE WHEN e.`turno` = 'manana' THEN '13:00:00' ELSE '19:00:00' END,
       CASE WHEN MOD(e.`id` + DAYOFYEAR(f.`fecha`), 5) = 1 THEN 4.75 ELSE 5.00 END,
       CASE WHEN MOD(e.`id` + DAYOFYEAR(f.`fecha`), 5) = 1 THEN 'tarde' ELSE 'a_tiempo' END
FROM `estudiantes` e CROSS JOIN `tmp_fechas_prueba` f
WHERE e.`codigo_estudiante` LIKE 'PRU-REG-%'
  AND f.`fecha` BETWEEN '2026-11-01' AND '2026-11-30'
  AND f.`fecha` <> CURDATE()
  AND MOD(e.`id` + DAYOFYEAR(f.`fecha`), 9) <> 0;

INSERT INTO `registros_asistencia`
    (`estudiante_id`, `fecha`, `hora_entrada`, `hora_salida`, `horas_totales`, `estado`)
SELECT e.`id`, f.`fecha`,
       CASE WHEN MOD(e.`id` + DAYOFYEAR(f.`fecha`), 5) = 1 AND e.`turno` = 'manana' THEN '08:15:00'
            WHEN MOD(e.`id` + DAYOFYEAR(f.`fecha`), 5) = 1 THEN '14:15:00'
            WHEN e.`turno` = 'manana' THEN '08:00:00' ELSE '14:00:00' END,
       CASE WHEN e.`turno` = 'manana' THEN '13:00:00' ELSE '19:00:00' END,
       CASE WHEN MOD(e.`id` + DAYOFYEAR(f.`fecha`), 5) = 1 THEN 4.75 ELSE 5.00 END,
       CASE WHEN MOD(e.`id` + DAYOFYEAR(f.`fecha`), 5) = 1 THEN 'tarde' ELSE 'a_tiempo' END
FROM `estudiantes` e CROSS JOIN `tmp_fechas_prueba` f
WHERE e.`codigo_estudiante` LIKE 'PRU-REG-%'
  AND f.`fecha` BETWEEN '2026-12-01' AND '2026-12-31'
  AND f.`fecha` <> CURDATE()
  AND MOD(e.`id` + DAYOFYEAR(f.`fecha`), 9) <> 0;

DROP TEMPORARY TABLE IF EXISTS `tmp_fechas_prueba`;

-- 5. Verificación compacta.
SELECT COUNT(*) AS estudiantes_prueba
FROM `estudiantes`
WHERE `codigo_estudiante` LIKE 'PRU-REG-%';

SELECT DATE_FORMAT(a.`fecha`, '%Y-%m') AS mes,
       SUM(a.`estado` = 'a_tiempo') AS asistencias_A,
       SUM(a.`estado` = 'tarde') AS tardanzas_T,
       COUNT(*) AS total_marcas
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE e.`codigo_estudiante` LIKE 'PRU-REG-%'
GROUP BY DATE_FORMAT(a.`fecha`, '%Y-%m')
ORDER BY mes;
