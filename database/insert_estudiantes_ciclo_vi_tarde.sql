-- Registro masivo: Ciclo VI, Semestre 4, turno tarde.
-- Requiere haber aplicado 20261015_rename_schema_to_spanish.sql.
-- Fuente: relación entregada por el instituto.
-- Edad, sexo y correo se dejan en NULL para completarlos posteriormente.
-- Este archivo se puede ejecutar más de una vez: actualiza por DNI sin duplicar estudiantes.

USE `control_acceso_db`;

START TRANSACTION;

INSERT INTO `especialidades` (`nombre`, `estado`)
VALUES ('DESARROLLO DE SISTEMAS DE INFORMACIÓN', 'activo')
ON DUPLICATE KEY UPDATE `estado` = 'activo';

INSERT INTO `ciclos` (`id`, `nombre`)
VALUES (6, 'Ciclo VI')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

INSERT INTO `semestres` (`id`, `nombre`)
VALUES (4, 'Semestre 4')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

SET @specialty_id := (
    SELECT `id` FROM `especialidades`
    WHERE `nombre` = 'DESARROLLO DE SISTEMAS DE INFORMACIÓN'
    LIMIT 1
);

INSERT INTO `estudiantes` (
    `codigo_estudiante`, `nombres`, `apellidos`, `dni`, `edad`, `sexo`, `correo`,
    `especialidad_id`, `ciclo_id`, `semestre_id`, `ciclo_legado`, `turno`,
    `ruta_foto`, `estado`
) VALUES
    ('EST-2026-0001', 'MARICIELO ABIGAIL', 'ARAMAYO PINEDO', '61035046', NULL, NULL, NULL, @specialty_id, 6, 4, NULL, 'tarde', 'default.png', 'activo'),
    ('EST-2026-0002', 'PEDRO ALDAIR', 'ARRIBASPLATA MOGOLLON', '75527112', NULL, NULL, NULL, @specialty_id, 6, 4, NULL, 'tarde', 'default.png', 'activo'),
    ('EST-2026-0003', 'ZINEDINE ANGELO', 'AVENDAÑO MORALES', '60806033', NULL, NULL, NULL, @specialty_id, 6, 4, NULL, 'tarde', 'default.png', 'activo'),
    ('EST-2026-0004', 'LUIS MIGUEL', 'CERNA JARA', '60806016', NULL, NULL, NULL, @specialty_id, 6, 4, NULL, 'tarde', 'default.png', 'activo'),
    ('EST-2026-0005', 'LUIS FERNANDO', 'INOÑAN SANTISTEBAN', '75100725', NULL, NULL, NULL, @specialty_id, 6, 4, NULL, 'tarde', 'default.png', 'activo'),
    ('EST-2026-0006', 'JORDAN ALBERT', 'LUIS USURIAGA', '60746114', NULL, NULL, NULL, @specialty_id, 6, 4, NULL, 'tarde', 'default.png', 'activo'),
    ('EST-2026-0007', 'DIEGO ESTEFANO', 'MEDRANO YNGAR', '75923808', NULL, NULL, NULL, @specialty_id, 6, 4, NULL, 'tarde', 'default.png', 'activo'),
    ('EST-2026-0008', 'JOSÉ VALENTINO', 'MENA FERNANDEZ', '60959200', NULL, NULL, NULL, @specialty_id, 6, 4, NULL, 'tarde', 'default.png', 'activo'),
    ('EST-2026-0009', 'KEREN SARAI', 'PACHAS CORCINO', '76035435', NULL, NULL, NULL, @specialty_id, 6, 4, NULL, 'tarde', 'default.png', 'activo'),
    ('EST-2026-0010', 'MAYLOO MIKSUCO', 'QUINECHE BONILLA', '60774186', NULL, NULL, NULL, @specialty_id, 6, 4, NULL, 'tarde', 'default.png', 'activo'),
    ('EST-2026-0011', 'ANTHONY JOSUÉ', 'ROJAS GONZALES', '60958140', NULL, NULL, NULL, @specialty_id, 6, 4, NULL, 'tarde', 'default.png', 'activo'),
    ('EST-2026-0012', 'JOSÉ MANUEL', 'ROSALES RAMOS', '75689045', NULL, NULL, NULL, @specialty_id, 6, 4, NULL, 'tarde', 'default.png', 'activo'),
    ('EST-2026-0013', 'SAMUEL AMOS', 'VILLANUEVA CHINCHAY', '60241160', NULL, NULL, NULL, @specialty_id, 6, 4, NULL, 'tarde', 'default.png', 'activo')
ON DUPLICATE KEY UPDATE
    `nombres` = VALUES(`nombres`),
    `apellidos` = VALUES(`apellidos`),
    `especialidad_id` = VALUES(`especialidad_id`),
    `ciclo_id` = VALUES(`ciclo_id`),
    `semestre_id` = VALUES(`semestre_id`),
    `ciclo_legado` = NULL,
    `turno` = VALUES(`turno`),
    `estado` = 'activo';

COMMIT;

-- Verificación esperada: 13 estudiantes del Ciclo VI, turno tarde.
SELECT
    e.`codigo_estudiante` AS `Código`,
    CONCAT(e.`apellidos`, ', ', e.`nombres`) AS `Estudiante`,
    e.`dni` AS `DNI`,
    d.`nombre` AS `Especialidad`,
    c.`nombre` AS `Ciclo`,
    s.`nombre` AS `Semestre`,
    e.`turno` AS `Turno`
FROM `estudiantes` e
INNER JOIN `especialidades` d ON d.`id` = e.`especialidad_id`
INNER JOIN `ciclos` c ON c.`id` = e.`ciclo_id`
INNER JOIN `semestres` s ON s.`id` = e.`semestre_id`
WHERE e.`dni` IN (
    '61035046', '75527112', '60806033', '60806016', '75100725',
    '60746114', '75923808', '60959200', '76035435', '60774186',
    '60958140', '75689045', '60241160'
)
ORDER BY e.`apellidos`, e.`nombres`;
