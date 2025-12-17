CREATE TABLE IF NOT EXISTS social_portfolio_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    kind ENUM('image','file') NOT NULL DEFAULT 'image',
    title VARCHAR(200) NULL,
    url VARCHAR(800) NOT NULL,
    mime_type VARCHAR(150) NULL,
    size_bytes INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_spm_item (item_id),
    INDEX idx_spm_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
