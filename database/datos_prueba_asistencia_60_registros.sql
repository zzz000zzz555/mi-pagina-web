-- Carga masiva de 60 asistencias de prueba.
-- 10 estudiantes × 6 días = 60 registros y sus movimientos detallados.
-- Rango reservado para pruebas: 01/10/2026 al 06/10/2026.
-- Puede ejecutarse varias veces: solo reemplaza las pruebas de este archivo.

USE `control_acceso_db`;

START TRANSACTION;

CREATE TEMPORARY TABLE `fechas_prueba_asistencia` (
    `fecha` DATE NOT NULL PRIMARY KEY
);

INSERT INTO `fechas_prueba_asistencia` (`fecha`) VALUES
    ('2026-10-01'), ('2026-10-02'), ('2026-10-03'),
    ('2026-10-04'), ('2026-10-05'), ('2026-10-06');

-- Elimina únicamente esta carga de prueba antes de recrearla.
DELETE m
FROM `movimientos_asistencia` m
INNER JOIN `registros_asistencia` a ON a.`id` = m.`registro_asistencia_id`
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
INNER JOIN `fechas_prueba_asistencia` f ON f.`fecha` = a.`fecha`
WHERE e.`codigo_estudiante` IN (
    'EST-2026-0001','EST-2026-0002','EST-2026-0003','EST-2026-0004','EST-2026-0005',
    'EST-2026-0006','EST-2026-0007','EST-2026-0008','EST-2026-0009','EST-2026-0010'
);

DELETE a
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
INNER JOIN `fechas_prueba_asistencia` f ON f.`fecha` = a.`fecha`
WHERE e.`codigo_estudiante` IN (
    'EST-2026-0001','EST-2026-0002','EST-2026-0003','EST-2026-0004','EST-2026-0005',
    'EST-2026-0006','EST-2026-0007','EST-2026-0008','EST-2026-0009','EST-2026-0010'
);

-- Limpieza única del rango anterior de este mismo archivo (01–06/09/2026).
DELETE m
FROM `movimientos_asistencia` m
INNER JOIN `registros_asistencia` a ON a.`id` = m.`registro_asistencia_id`
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` BETWEEN '2026-09-01' AND '2026-09-06'
  AND e.`codigo_estudiante` IN (
    'EST-2026-0001','EST-2026-0002','EST-2026-0003','EST-2026-0004','EST-2026-0005',
    'EST-2026-0006','EST-2026-0007','EST-2026-0008','EST-2026-0009','EST-2026-0010'
);

DELETE a
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
WHERE a.`fecha` BETWEEN '2026-09-01' AND '2026-09-06'
  AND e.`codigo_estudiante` IN (
    'EST-2026-0001','EST-2026-0002','EST-2026-0003','EST-2026-0004','EST-2026-0005',
    'EST-2026-0006','EST-2026-0007','EST-2026-0008','EST-2026-0009','EST-2026-0010'
);

-- Patrón de prueba:
-- 0: puntual; 1: tardanza + atención médica + retorno;
-- 2: refrigerio + retorno; 3: tardanza con salida final.
INSERT INTO `registros_asistencia`
    (`estudiante_id`, `fecha`, `hora_entrada`, `hora_salida`, `horas_totales`, `estado`)
SELECT
    e.`id`, f.`fecha`,
    CASE MOD(e.`id` + DAY(f.`fecha`), 4)
        WHEN 0 THEN '13:55:00'
        WHEN 1 THEN '14:15:00'
        WHEN 2 THEN '13:58:00'
        ELSE '14:08:00'
    END,
    CASE MOD(e.`id` + DAY(f.`fecha`), 4)
        WHEN 0 THEN '19:00:00'
        WHEN 1 THEN '19:10:00'
        WHEN 2 THEN '19:05:00'
        ELSE '19:05:00'
    END,
    CASE MOD(e.`id` + DAY(f.`fecha`), 4)
        WHEN 0 THEN 5.08
        WHEN 1 THEN 4.42
        WHEN 2 THEN 4.58
        ELSE 4.95
    END,
    CASE MOD(e.`id` + DAY(f.`fecha`), 4)
        WHEN 0 THEN 'a_tiempo'
        WHEN 2 THEN 'a_tiempo'
        ELSE 'tarde'
    END
FROM `estudiantes` e
CROSS JOIN `fechas_prueba_asistencia` f
WHERE e.`codigo_estudiante` IN (
    'EST-2026-0001','EST-2026-0002','EST-2026-0003','EST-2026-0004','EST-2026-0005',
    'EST-2026-0006','EST-2026-0007','EST-2026-0008','EST-2026-0009','EST-2026-0010'
)
  AND e.`estado` = 'activo';

-- Entrada para los 60 registros.
INSERT INTO `movimientos_asistencia`
    (`registro_asistencia_id`, `estudiante_id`, `tipo`, `fecha_hora`, `motivo`)
SELECT a.`id`, a.`estudiante_id`, 'entrada', CONCAT(a.`fecha`, ' ', a.`hora_entrada`), NULL
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
INNER JOIN `fechas_prueba_asistencia` f ON f.`fecha` = a.`fecha`
WHERE e.`codigo_estudiante` IN (
    'EST-2026-0001','EST-2026-0002','EST-2026-0003','EST-2026-0004','EST-2026-0005',
    'EST-2026-0006','EST-2026-0007','EST-2026-0008','EST-2026-0009','EST-2026-0010'
);

-- Salidas temporales: médico (patrón 1) y refrigerio (patrón 2).
INSERT INTO `movimientos_asistencia`
    (`registro_asistencia_id`, `estudiante_id`, `tipo`, `fecha_hora`, `motivo`)
SELECT a.`id`, a.`estudiante_id`, 'salida_temporal',
       CONCAT(a.`fecha`, CASE MOD(a.`estudiante_id` + DAY(a.`fecha`), 4) WHEN 1 THEN ' 16:00:00' ELSE ' 15:30:00' END),
       CASE MOD(a.`estudiante_id` + DAY(a.`fecha`), 4) WHEN 1 THEN 'Atención médica programada' ELSE 'Refrigerio' END
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
INNER JOIN `fechas_prueba_asistencia` f ON f.`fecha` = a.`fecha`
WHERE e.`codigo_estudiante` IN (
    'EST-2026-0001','EST-2026-0002','EST-2026-0003','EST-2026-0004','EST-2026-0005',
    'EST-2026-0006','EST-2026-0007','EST-2026-0008','EST-2026-0009','EST-2026-0010'
)
  AND MOD(a.`estudiante_id` + DAY(a.`fecha`), 4) IN (1, 2);

-- Retornos de las salidas temporales.
INSERT INTO `movimientos_asistencia`
    (`registro_asistencia_id`, `estudiante_id`, `tipo`, `fecha_hora`, `motivo`)
SELECT a.`id`, a.`estudiante_id`, 'reingreso',
       CONCAT(a.`fecha`, CASE MOD(a.`estudiante_id` + DAY(a.`fecha`), 4) WHEN 1 THEN ' 16:30:00' ELSE ' 16:05:00' END), NULL
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
INNER JOIN `fechas_prueba_asistencia` f ON f.`fecha` = a.`fecha`
WHERE e.`codigo_estudiante` IN (
    'EST-2026-0001','EST-2026-0002','EST-2026-0003','EST-2026-0004','EST-2026-0005',
    'EST-2026-0006','EST-2026-0007','EST-2026-0008','EST-2026-0009','EST-2026-0010'
)
  AND MOD(a.`estudiante_id` + DAY(a.`fecha`), 4) IN (1, 2);

-- Salida final para los 60 registros.
INSERT INTO `movimientos_asistencia`
    (`registro_asistencia_id`, `estudiante_id`, `tipo`, `fecha_hora`, `motivo`)
SELECT a.`id`, a.`estudiante_id`, 'salida_final', CONCAT(a.`fecha`, ' ', a.`hora_salida`), NULL
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
INNER JOIN `fechas_prueba_asistencia` f ON f.`fecha` = a.`fecha`
WHERE e.`codigo_estudiante` IN (
    'EST-2026-0001','EST-2026-0002','EST-2026-0003','EST-2026-0004','EST-2026-0005',
    'EST-2026-0006','EST-2026-0007','EST-2026-0008','EST-2026-0009','EST-2026-0010'
);

COMMIT;

-- Validación: debe devolver 60 asistencias y más de 120 movimientos.
SELECT
    COUNT(*) AS `asistencias_prueba`,
    (SELECT COUNT(*)
     FROM `movimientos_asistencia` m
     INNER JOIN `registros_asistencia` a ON a.`id` = m.`registro_asistencia_id`
     INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
     INNER JOIN `fechas_prueba_asistencia` f ON f.`fecha` = a.`fecha`
     WHERE e.`codigo_estudiante` IN (
        'EST-2026-0001','EST-2026-0002','EST-2026-0003','EST-2026-0004','EST-2026-0005',
        'EST-2026-0006','EST-2026-0007','EST-2026-0008','EST-2026-0009','EST-2026-0010'
     )) AS `movimientos_prueba`
FROM `registros_asistencia` a
INNER JOIN `estudiantes` e ON e.`id` = a.`estudiante_id`
INNER JOIN `fechas_prueba_asistencia` f ON f.`fecha` = a.`fecha`
WHERE e.`codigo_estudiante` IN (
    'EST-2026-0001','EST-2026-0002','EST-2026-0003','EST-2026-0004','EST-2026-0005',
    'EST-2026-0006','EST-2026-0007','EST-2026-0008','EST-2026-0009','EST-2026-0010'
);

DROP TEMPORARY TABLE `fechas_prueba_asistencia`;
