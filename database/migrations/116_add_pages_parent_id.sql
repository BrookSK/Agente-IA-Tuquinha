ALTER TABLE pages
    ADD COLUMN parent_id INT UNSIGNED NULL AFTER owner_user_id,
    ADD INDEX idx_pages_parent_id (parent_id),
    ADD CONSTRAINT fk_pages_parent_id FOREIGN KEY (parent_id) REFERENCES pages(id) ON DELETE SET NULL;
