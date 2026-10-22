-- Agrega el semestre académico a los estudiantes.
-- Los registros existentes pertenecen al semestre 4.
ALTER TABLE employees
    ADD COLUMN semester TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER position,
    ADD CONSTRAINT chk_employees_semester CHECK (semester BETWEEN 1 AND 5);

UPDATE employees SET semester = 4;
