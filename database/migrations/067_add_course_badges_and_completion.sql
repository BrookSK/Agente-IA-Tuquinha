ALTER TABLE courses
    ADD COLUMN badge_image_path VARCHAR(255) NULL AFTER image_path;

CREATE TABLE IF NOT EXISTS user_course_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    testimonial_text TEXT NULL,
    rating TINYINT NULL,
    earned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_course_badge (user_id, course_id),
    INDEX idx_ucb_user (user_id),
    INDEX idx_ucb_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
