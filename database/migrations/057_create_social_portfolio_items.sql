CREATE TABLE IF NOT EXISTS social_portfolio_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    external_url VARCHAR(800) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_spi_user (user_id),
    INDEX idx_spi_project (project_id),
    INDEX idx_spi_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
