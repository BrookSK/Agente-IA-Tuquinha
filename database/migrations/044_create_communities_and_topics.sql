CREATE TABLE IF NOT EXISTS communities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_user_id INT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    image_path VARCHAR(255) NULL,
    members_count INT NOT NULL DEFAULT 0,
    topics_count INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    INDEX idx_communities_owner (owner_user_id),
    INDEX idx_communities_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    community_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'member',
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    left_at DATETIME NULL,
    UNIQUE KEY uniq_cm_member (community_id, user_id),
    INDEX idx_cm_community (community_id),
    INDEX idx_cm_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    community_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NULL,
    replies_count INT NOT NULL DEFAULT 0,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    is_sticky TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_ct_community (community_id),
    INDEX idx_ct_user (user_id),
    INDEX idx_ct_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_topic_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    user_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_ctp_topic (topic_id),
    INDEX idx_ctp_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
