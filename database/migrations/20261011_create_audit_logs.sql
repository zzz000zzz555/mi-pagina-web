-- Bitácora inmutable de acciones realizadas en el sistema.
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id INT UNSIGNED NULL,
    actor_employee_id INT UNSIGNED NULL,
    actor_type VARCHAR(20) NOT NULL DEFAULT 'sistema',
    actor_name VARCHAR(150) NOT NULL,
    action VARCHAR(30) NOT NULL,
    entity VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    description VARCHAR(500) NOT NULL,
    details_json LONGTEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_logs_created_at (created_at),
    KEY idx_audit_logs_entity (entity, entity_id),
    KEY idx_audit_logs_action (action),
    KEY idx_audit_logs_actor_user (actor_user_id),
    KEY idx_audit_logs_actor_employee (actor_employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
