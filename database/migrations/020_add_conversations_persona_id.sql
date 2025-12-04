ALTER TABLE conversations
    ADD COLUMN persona_id INT UNSIGNED NULL AFTER user_id,
    ADD CONSTRAINT fk_conversations_persona FOREIGN KEY (persona_id) REFERENCES personalities(id) ON DELETE SET NULL,
    ADD INDEX idx_conversations_persona_id (persona_id);
