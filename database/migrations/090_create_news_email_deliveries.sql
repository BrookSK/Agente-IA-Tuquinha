CREATE TABLE news_email_deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    news_item_id INT NOT NULL,
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_news_delivery (user_id, news_item_id),
    KEY idx_news_delivery_user (user_id),
    KEY idx_news_delivery_news (news_item_id),
    CONSTRAINT fk_news_email_deliveries_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_news_email_deliveries_news
        FOREIGN KEY (news_item_id) REFERENCES news_items(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
