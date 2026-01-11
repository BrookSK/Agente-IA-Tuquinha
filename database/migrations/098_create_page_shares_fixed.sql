-- Corrige errno 150 (FK incorretamente formado) garantindo tipos compatíveis com pages.id e users.id

CREATE TABLE IF NOT EXISTS page_shares (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role VARCHAR(16) NOT NULL DEFAULT 'view',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_page_shares_page_user (page_id, user_id),
    INDEX idx_page_shares_user_id (user_id),
    CONSTRAINT fk_page_shares_page_id FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    CONSTRAINT fk_page_shares_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
