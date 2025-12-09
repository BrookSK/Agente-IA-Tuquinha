CREATE TABLE IF NOT EXISTS user_social_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    about_me TEXT NULL,
    interests TEXT NULL,
    favorite_music TEXT NULL,
    favorite_movies TEXT NULL,
    favorite_books TEXT NULL,
    website VARCHAR(255) NULL,
    visits_count INT NOT NULL DEFAULT 0,
    last_visit_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uniq_usp_user (user_id),
    INDEX idx_usp_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_friends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_user_id INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    requested_by_user_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME NULL,
    UNIQUE KEY uniq_friend_pair (user_id, friend_user_id),
    INDEX idx_uf_user (user_id),
    INDEX idx_uf_friend (friend_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
