CREATE TABLE IF NOT EXISTS social_portfolio_collaborators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_user_id INT NOT NULL,
    collaborator_user_id INT NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'read',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uniq_spc_pair (owner_user_id, collaborator_user_id),
    INDEX idx_spc_owner (owner_user_id),
    INDEX idx_spc_collab (collaborator_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_portfolio_invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_user_id INT NOT NULL,
    inviter_user_id INT NOT NULL,
    invited_email VARCHAR(255) NOT NULL,
    invited_name VARCHAR(255) NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'read',
    token VARCHAR(80) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME NULL,
    UNIQUE KEY uniq_spi_token (token),
    INDEX idx_spi_owner (owner_user_id),
    INDEX idx_spi_email (invited_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
