CREATE TABLE course_lives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    scheduled_at DATETIME NOT NULL,
    meet_link VARCHAR(500) NULL,
    google_event_id VARCHAR(255) NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    INDEX idx_course_lives_course (course_id),
    INDEX idx_course_lives_scheduled (scheduled_at)
);
