-- Ejecutar una sola vez sobre control_acceso_db.
-- Conserva todos los datos existentes.
USE control_acceso_db;

ALTER TABLE employees
    ADD COLUMN shift ENUM('manana','tarde') NOT NULL DEFAULT 'manana' AFTER position;

ALTER TABLE attendance_logs
    ADD UNIQUE KEY uq_attendance_employee_date (employee_id, date_log),
    ADD KEY idx_attendance_date_status (date_log, status);

ALTER TABLE users
    ADD UNIQUE KEY uq_users_username (username);

ALTER TABLE departments
    ADD UNIQUE KEY uq_departments_name (name);

ALTER TABLE visitors
    ADD UNIQUE KEY uq_visitors_dni (dni);

INSERT INTO settings (setting_name, setting_value) VALUES
    ('institution_name', 'IES José Santos Chocano'),
    ('morning_entry_time', '08:00'),
    ('afternoon_entry_time', '14:00'),
    ('timezone', 'America/Lima')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

