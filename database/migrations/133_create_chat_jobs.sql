CREATE TABLE chat_jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    conversation_id INT UNSIGNED NOT NULL,
    user_message_id INT UNSIGNED NOT NULL,
    status ENUM('pending','running','done','error') NOT NULL DEFAULT 'pending',
    assistant_message_id INT UNSIGNED NULL,
    tokens_used INT UNSIGNED NULL,
    error_text TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chat_jobs_status_created (status, created_at),
    INDEX idx_chat_jobs_conversation (conversation_id),
    CONSTRAINT fk_chat_jobs_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_jobs_user_message FOREIGN KEY (user_message_id) REFERENCES messages(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_jobs_assistant_message FOREIGN KEY (assistant_message_id) REFERENCES messages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
