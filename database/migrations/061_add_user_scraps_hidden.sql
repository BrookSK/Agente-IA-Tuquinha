ALTER TABLE user_scraps
    ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER is_deleted,
    ADD COLUMN hidden_at DATETIME NULL AFTER deleted_at,
    ADD INDEX idx_us_hidden (to_user_id, is_hidden);
