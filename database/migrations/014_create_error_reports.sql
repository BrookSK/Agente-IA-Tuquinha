CREATE TABLE error_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    conversation_id INT UNSIGNED NULL,
    message_id INT UNSIGNED NULL,
    tokens_used INT NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    user_comment TEXT NULL,
    status ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_error_reports_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
