CREATE TABLE IF NOT EXISTS social_portfolio_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    type ENUM('text','image','gallery','video','embed') NOT NULL DEFAULT 'text',
    text_content MEDIUMTEXT NULL,
    media_url VARCHAR(800) NULL,
    media_mime VARCHAR(150) NULL,
    meta_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_spb_item (item_id),
    INDEX idx_spb_item_order (item_id, sort_order),
    INDEX idx_spb_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_portfolio_block_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    block_id INT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    url VARCHAR(800) NOT NULL,
    mime_type VARCHAR(150) NULL,
    title VARCHAR(200) NULL,
    size_bytes INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_spbm_block (block_id),
    INDEX idx_spbm_block_order (block_id, sort_order),
    INDEX idx_spbm_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE social_portfolio_items
    ADD COLUMN cover_url VARCHAR(800) NULL AFTER external_url,
    ADD COLUMN cover_mime VARCHAR(150) NULL AFTER cover_url,
    ADD COLUMN cover_updated_at DATETIME NULL AFTER cover_mime;
