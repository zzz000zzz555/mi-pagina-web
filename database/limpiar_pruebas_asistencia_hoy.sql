-- Libera los alumnos ficticios PRU-REG para probar el Kiosco HOY.
-- Solo borra asistencias y movimientos de prueba de la fecha actual.
-- No modifica estudiantes ni registros reales.

USE `control_acceso_db`;

START TRANSACTION;

DELETE m
FROM `movimientos_asistencia` m
INNER JOIN `registros_asistencia` a ON a.`id` = m.`registro_asistencia_id`
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE e.`codigo_estudiante` LIKE 'PRU-REG-%'
  AND a.`fecha` = CURDATE();

DELETE a
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE e.`codigo_estudiante` LIKE 'PRU-REG-%'
  AND a.`fecha` = CURDATE();

COMMIT;

-- Debe devolver 0: confirma que los QR PRU-REG están listos para registrar hoy.
SELECT COUNT(*) AS asistencias_prueba_hoy
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE e.`codigo_estudiante` LIKE 'PRU-REG-%'
  AND a.`fecha` = CURDATE();
