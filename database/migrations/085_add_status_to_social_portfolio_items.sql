ALTER TABLE social_portfolio_items
    ADD COLUMN status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    ADD COLUMN published_at DATETIME NULL,
    ADD INDEX idx_spi_status (status),
    ADD INDEX idx_spi_published_at (published_at);

UPDATE social_portfolio_items
SET status = 'published',
    published_at = COALESCE(published_at, created_at, NOW())
WHERE deleted_at IS NULL;
