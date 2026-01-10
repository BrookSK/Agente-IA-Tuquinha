CREATE TABLE news_item_contents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_item_id INT NOT NULL,
    extracted_title VARCHAR(255) NULL,
    extracted_description TEXT NULL,
    extracted_text MEDIUMTEXT NULL,
    extracted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_news_item_content (news_item_id),
    CONSTRAINT fk_news_item_contents_item
        FOREIGN KEY (news_item_id) REFERENCES news_items(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
