CREATE TABLE community_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    body TEXT NULL,
    image_path VARCHAR(255) NULL,
    file_path VARCHAR(255) NULL,
    repost_post_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_cp_user (user_id),
    INDEX idx_cp_repost (repost_post_id)
);
