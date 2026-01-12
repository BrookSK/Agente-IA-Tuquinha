CREATE TABLE kanban_card_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_id INT UNSIGNED NOT NULL,
    url VARCHAR(1024) NOT NULL,
    original_name VARCHAR(255) NULL,
    mime_type VARCHAR(150) NULL,
    size INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kanban_card_attachments_card_id (card_id),
    CONSTRAINT fk_kanban_card_attachments_card_id FOREIGN KEY (card_id) REFERENCES kanban_cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
