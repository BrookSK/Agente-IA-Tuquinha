CREATE TABLE page_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(16) NOT NULL DEFAULT 'view',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_page_shares_page_user (page_id, user_id),
    INDEX idx_page_shares_user_id (user_id),
    CONSTRAINT fk_page_shares_page_id FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    CONSTRAINT fk_page_shares_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
