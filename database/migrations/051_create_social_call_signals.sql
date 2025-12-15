CREATE TABLE IF NOT EXISTS social_call_signals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_user_id INT NOT NULL,
    type VARCHAR(32) NOT NULL,
    payload TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_scs_conversation (conversation_id),
    INDEX idx_scs_conversation_id (conversation_id, id),
    INDEX idx_scs_sender (sender_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
