CREATE TABLE IF NOT EXISTS project_invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    inviter_user_id INT NOT NULL,
    invited_email VARCHAR(255) NOT NULL,
    invited_name VARCHAR(255) NULL,
    role ENUM('read','write','admin') NOT NULL DEFAULT 'read',
    token VARCHAR(64) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending, accepted, cancelled, expired
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME NULL,
    INDEX idx_pi_project (project_id),
    INDEX idx_pi_email (invited_email),
    UNIQUE KEY uniq_pi_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
