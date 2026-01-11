CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_user_id INT NOT NULL,
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
);
