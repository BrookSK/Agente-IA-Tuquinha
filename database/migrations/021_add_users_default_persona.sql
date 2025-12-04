ALTER TABLE users
    ADD COLUMN default_persona_id INT UNSIGNED NULL AFTER preferred_name,
    ADD CONSTRAINT fk_users_default_persona FOREIGN KEY (default_persona_id) REFERENCES personalities(id) ON DELETE SET NULL,
    ADD INDEX idx_users_default_persona_id (default_persona_id);
