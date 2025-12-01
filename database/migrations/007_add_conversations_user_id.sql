ALTER TABLE conversations
ADD COLUMN user_id INT UNSIGNED NULL AFTER session_id,
ADD CONSTRAINT fk_conversations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
ADD INDEX idx_conversations_user_id (user_id);
