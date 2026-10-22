-- Datos de prueba para el historial de asistencia.
-- Inserta: asistencia completa, tardanza, salida temporal, reingreso y salida final.
-- IMPORTANTE: usa solo una fecha que NO contenga registros reales.
-- Para cambiar la fecha de prueba, modifica la siguiente línea.

USE `control_acceso_db`;

SET @fecha_prueba = '2026-10-14';

START TRANSACTION;

-- Limpia únicamente las pruebas anteriores de esta fecha y de estos cuatro estudiantes.
DELETE m
FROM `movimientos_asistencia` m
INNER JOIN `registros_asistencia` a ON a.`id` = m.`registro_asistencia_id`
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` = @fecha_prueba
  AND e.`codigo_estudiante` IN ('EST-2026-0001', 'EST-2026-0002', 'EST-2026-0003', 'EST-2026-0004');

DELETE a
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` = @fecha_prueba
  AND e.`codigo_estudiante` IN ('EST-2026-0001', 'EST-2026-0002', 'EST-2026-0003', 'EST-2026-0004');

-- 1. Asistencia normal: entrada y salida final.
-- 2. Tardanza + salida por atención médica + retorno.
-- 3. Salida por refrigerio + retorno.
-- 4. Asistencia en curso: aún no registra salida final.
INSERT INTO `registros_asistencia`
    (`estudiante_id`, `fecha`, `hora_entrada`, `hora_salida`, `horas_totales`, `estado`)
SELECT `id`, @fecha_prueba, '13:55:00', '19:03:00', 5.13, 'a_tiempo'
FROM `estudiantes` WHERE `codigo_estudiante` = 'EST-2026-0001'
UNION ALL
SELECT `id`, @fecha_prueba, '14:18:00', '19:10:00', 4.20, 'tarde'
FROM `estudiantes` WHERE `codigo_estudiante` = 'EST-2026-0002'
UNION ALL
SELECT `id`, @fecha_prueba, '13:58:00', '19:00:00', 4.58, 'a_tiempo'
FROM `estudiantes` WHERE `codigo_estudiante` = 'EST-2026-0003'
UNION ALL
SELECT `id`, @fecha_prueba, '14:08:00', NULL, NULL, 'tarde'
FROM `estudiantes` WHERE `codigo_estudiante` = 'EST-2026-0004';

INSERT INTO `movimientos_asistencia`
    (`registro_asistencia_id`, `estudiante_id`, `tipo`, `fecha_hora`, `motivo`)
SELECT a.`id`, a.`estudiante_id`, 'entrada', CONCAT(@fecha_prueba, ' 13:55:00'), NULL
FROM `registros_asistencia` a INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` = @fecha_prueba AND e.`codigo_estudiante` = 'EST-2026-0001'
UNION ALL
SELECT a.`id`, a.`estudiante_id`, 'salida_final', CONCAT(@fecha_prueba, ' 19:03:00'), NULL
FROM `registros_asistencia` a INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` = @fecha_prueba AND e.`codigo_estudiante` = 'EST-2026-0001'
UNION ALL
SELECT a.`id`, a.`estudiante_id`, 'entrada', CONCAT(@fecha_prueba, ' 14:18:00'), NULL
FROM `registros_asistencia` a INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` = @fecha_prueba AND e.`codigo_estudiante` = 'EST-2026-0002'
UNION ALL
SELECT a.`id`, a.`estudiante_id`, 'salida_temporal', CONCAT(@fecha_prueba, ' 16:05:00'), 'Atención médica programada'
FROM `registros_asistencia` a INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` = @fecha_prueba AND e.`codigo_estudiante` = 'EST-2026-0002'
UNION ALL
SELECT a.`id`, a.`estudiante_id`, 'reingreso', CONCAT(@fecha_prueba, ' 16:45:00'), NULL
FROM `registros_asistencia` a INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` = @fecha_prueba AND e.`codigo_estudiante` = 'EST-2026-0002'
UNION ALL
SELECT a.`id`, a.`estudiante_id`, 'salida_final', CONCAT(@fecha_prueba, ' 19:10:00'), NULL
FROM `registros_asistencia` a INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` = @fecha_prueba AND e.`codigo_estudiante` = 'EST-2026-0002'
UNION ALL
SELECT a.`id`, a.`estudiante_id`, 'entrada', CONCAT(@fecha_prueba, ' 13:58:00'), NULL
FROM `registros_asistencia` a INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` = @fecha_prueba AND e.`codigo_estudiante` = 'EST-2026-0003'
UNION ALL
SELECT a.`id`, a.`estudiante_id`, 'salida_temporal', CONCAT(@fecha_prueba, ' 15:33:00'), 'Refrigerio'
FROM `registros_asistencia` a INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` = @fecha_prueba AND e.`codigo_estudiante` = 'EST-2026-0003'
UNION ALL
SELECT a.`id`, a.`estudiante_id`, 'reingreso', CONCAT(@fecha_prueba, ' 16:00:00'), NULL
FROM `registros_asistencia` a INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` = @fecha_prueba AND e.`codigo_estudiante` = 'EST-2026-0003'
UNION ALL
SELECT a.`id`, a.`estudiante_id`, 'salida_final', CONCAT(@fecha_prueba, ' 19:00:00'), NULL
FROM `registros_asistencia` a INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` = @fecha_prueba AND e.`codigo_estudiante` = 'EST-2026-0003'
UNION ALL
SELECT a.`id`, a.`estudiante_id`, 'entrada', CONCAT(@fecha_prueba, ' 14:08:00'), NULL
FROM `registros_asistencia` a INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` = @fecha_prueba AND e.`codigo_estudiante` = 'EST-2026-0004';

COMMIT;

-- Verificación: deben mostrarse 4 estudiantes y sus movimientos en orden.
SELECT
    a.`fecha`,
    e.`codigo_estudiante` AS `codigo`,
    CONCAT(e.`apellidos`, ', ', e.`nombres`) AS `estudiante`,
    a.`hora_entrada`, a.`hora_salida`, a.`horas_totales`, a.`estado`,
    m.`tipo` AS `movimiento`, m.`fecha_hora`, m.`motivo`
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
LEFT JOIN `movimientos_asistencia` m ON m.`registro_asistencia_id` = a.`id`
WHERE a.`fecha` = @fecha_prueba
  AND e.`codigo_estudiante` IN ('EST-2026-0001', 'EST-2026-0002', 'EST-2026-0003', 'EST-2026-0004')
ORDER BY e.`codigo_estudiante`, m.`fecha_hora`, m.`id`;
