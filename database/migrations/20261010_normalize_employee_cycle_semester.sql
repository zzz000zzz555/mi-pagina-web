-- Normaliza el ciclo y el semestre como catálogos con claves foráneas.
-- Los valores heredados no reconocidos se conservan en legacy_cycle para revisión.
CREATE TABLE cycles (
    id TINYINT UNSIGNED NOT NULL,
    name VARCHAR(20) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cycles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO cycles (id, name) VALUES
    (1, 'Ciclo I'),
    (2, 'Ciclo II'),
    (3, 'Ciclo III'),
    (4, 'Ciclo IV'),
    (5, 'Ciclo V'),
    (6, 'Ciclo VI');

CREATE TABLE semesters (
    id TINYINT UNSIGNED NOT NULL,
    name VARCHAR(20) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_semesters_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO semesters (id, name) VALUES
    (1, 'Semestre 1'),
    (2, 'Semestre 2'),
    (3, 'Semestre 3'),
    (4, 'Semestre 4'),
    (5, 'Semestre 5');

ALTER TABLE employees
    DROP CONSTRAINT chk_employees_semester;

ALTER TABLE employees
    ADD COLUMN cycle_id TINYINT UNSIGNED NULL AFTER department_id,
    ADD COLUMN semester_id TINYINT UNSIGNED NULL AFTER cycle_id,
    CHANGE COLUMN position legacy_cycle VARCHAR(100) NULL,
    CHANGE COLUMN semester legacy_semester TINYINT UNSIGNED NULL DEFAULT NULL;

UPDATE employees
SET cycle_id = CASE
    WHEN TRIM(REPLACE(REPLACE(REPLACE(REPLACE(UPPER(legacy_cycle), 'CICLO', ''), ' ', ''), '-', ''), '.', '')) IN ('I', '1', '01') THEN 1
    WHEN TRIM(REPLACE(REPLACE(REPLACE(REPLACE(UPPER(legacy_cycle), 'CICLO', ''), ' ', ''), '-', ''), '.', '')) IN ('II', '2', '02') THEN 2
    WHEN TRIM(REPLACE(REPLACE(REPLACE(REPLACE(UPPER(legacy_cycle), 'CICLO', ''), ' ', ''), '-', ''), '.', '')) IN ('III', '3', '03') THEN 3
    WHEN TRIM(REPLACE(REPLACE(REPLACE(REPLACE(UPPER(legacy_cycle), 'CICLO', ''), ' ', ''), '-', ''), '.', '')) IN ('IV', '4', '04') THEN 4
    WHEN TRIM(REPLACE(REPLACE(REPLACE(REPLACE(UPPER(legacy_cycle), 'CICLO', ''), ' ', ''), '-', ''), '.', '')) IN ('V', '5', '05') THEN 5
    WHEN TRIM(REPLACE(REPLACE(REPLACE(REPLACE(UPPER(legacy_cycle), 'CICLO', ''), ' ', ''), '-', ''), '.', '')) IN ('VI', '6', '06') THEN 6
    ELSE NULL
END,
semester_id = CASE
    WHEN legacy_semester BETWEEN 1 AND 5 THEN legacy_semester
    ELSE NULL
END;

-- Solo los textos que no se pudieron interpretar permanecen como legado.
UPDATE employees SET legacy_cycle = NULL WHERE cycle_id IS NOT NULL;

ALTER TABLE employees
    DROP COLUMN legacy_semester;

ALTER TABLE employees
    ADD KEY idx_employees_cycle_id (cycle_id),
    ADD KEY idx_employees_semester_id (semester_id),
    ADD CONSTRAINT fk_employees_cycle
        FOREIGN KEY (cycle_id) REFERENCES cycles (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_employees_semester
        FOREIGN KEY (semester_id) REFERENCES semesters (id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- Verifique y asigne manualmente cualquier ciclo heredado que no se haya podido mapear:
-- SELECT id, employee_code, legacy_cycle FROM employees WHERE cycle_id IS NULL;
