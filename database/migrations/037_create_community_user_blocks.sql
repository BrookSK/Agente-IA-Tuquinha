CREATE TABLE community_user_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reason TEXT NOT NULL,
    blocked_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    unblocked_at DATETIME NULL,
    unblocked_by INT NULL,
    INDEX idx_cub_user (user_id)
);
