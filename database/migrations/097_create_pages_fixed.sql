-- Corrige errno 150 (FK incorretamente formado) garantindo tipos compatíveis com users.id
-- No schema atual, users.id = INT UNSIGNED. Portanto, as colunas FK devem ser INT UNSIGNED.

CREATE TABLE IF NOT EXISTS pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    icon VARCHAR(32) NULL,
    content_json LONGTEXT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    public_token VARCHAR(64) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pages_owner_user_id (owner_user_id),
    UNIQUE KEY uq_pages_public_token (public_token),
    CONSTRAINT fk_pages_owner_user_id FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
