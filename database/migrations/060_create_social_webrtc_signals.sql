CREATE TABLE IF NOT EXISTS social_webrtc_signals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    kind VARCHAR(16) NOT NULL,
    payload_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    delivered_at DATETIME NULL,
    INDEX idx_sws_conv_to (conversation_id, to_user_id, id),
    INDEX idx_sws_conv (conversation_id),
    INDEX idx_sws_to (to_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
