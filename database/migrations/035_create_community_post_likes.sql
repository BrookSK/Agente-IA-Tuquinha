CREATE TABLE community_post_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_like_post_user (post_id, user_id),
    INDEX idx_cpl_post (post_id),
    INDEX idx_cpl_user (user_id)
);
