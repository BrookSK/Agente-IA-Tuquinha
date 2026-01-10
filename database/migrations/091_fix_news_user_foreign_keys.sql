-- Corrige errno 150 (FK incorretamente formado) garantindo que o tipo do user_id seja compatível com users.id
-- No schema atual, users.id = INT UNSIGNED. Portanto, as colunas FK devem ser INT UNSIGNED.

-- 1) news_user_preferences
CREATE TABLE IF NOT EXISTS news_user_preferences (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,
    email_enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Se a tabela já existir, ajusta tipo e recria FK (de forma condicional)
SET @tbl_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'news_user_preferences'
);

SET @sql := IF(@tbl_exists > 0,
    'ALTER TABLE news_user_preferences MODIFY user_id INT UNSIGNED NOT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'news_user_preferences'
      AND CONSTRAINT_NAME = 'fk_news_user_preferences_user'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql := IF(@fk_exists > 0,
    'ALTER TABLE news_user_preferences DROP FOREIGN KEY fk_news_user_preferences_user',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE news_user_preferences
    ADD CONSTRAINT fk_news_user_preferences_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE;

-- 2) news_email_deliveries
CREATE TABLE IF NOT EXISTS news_email_deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    news_item_id INT NOT NULL,
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_news_delivery (user_id, news_item_id),
    KEY idx_news_delivery_user (user_id),
    KEY idx_news_delivery_news (news_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @tbl_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'news_email_deliveries'
);

SET @sql := IF(@tbl_exists > 0,
    'ALTER TABLE news_email_deliveries MODIFY user_id INT UNSIGNED NOT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'news_email_deliveries'
      AND CONSTRAINT_NAME = 'fk_news_email_deliveries_user'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql := IF(@fk_exists > 0,
    'ALTER TABLE news_email_deliveries DROP FOREIGN KEY fk_news_email_deliveries_user',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'news_email_deliveries'
      AND CONSTRAINT_NAME = 'fk_news_email_deliveries_news'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql := IF(@fk_exists > 0,
    'ALTER TABLE news_email_deliveries DROP FOREIGN KEY fk_news_email_deliveries_news',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE news_email_deliveries
    ADD CONSTRAINT fk_news_email_deliveries_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    ADD CONSTRAINT fk_news_email_deliveries_news
        FOREIGN KEY (news_item_id) REFERENCES news_items(id)
        ON DELETE CASCADE;
