CREATE TABLE IF NOT EXISTS community_invites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    community_id INT NOT NULL,
    inviter_user_id INT NOT NULL,
    invited_email VARCHAR(255) NOT NULL,
    invited_name VARCHAR(255) NULL,
    token VARCHAR(64) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending, accepted, cancelled, expired
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME NULL,
    INDEX idx_ci_community (community_id),
    INDEX idx_ci_email (invited_email),
    UNIQUE KEY uniq_ci_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_member_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    community_id INT NOT NULL,
    reporter_user_id INT NOT NULL,
    reported_user_id INT NOT NULL,
    reason TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open', -- open, resolved, dismissed
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    resolved_by INT NULL,
    INDEX idx_cmr_community (community_id),
    INDEX idx_cmr_reported (reported_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
