CREATE TABLE IF NOT EXISTS course_allowed_communities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    community_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_course_community (course_id, community_id),
    INDEX idx_course (course_id),
    INDEX idx_community (community_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
