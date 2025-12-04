CREATE TABLE course_lesson_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    lesson_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_clc_course (course_id),
    INDEX idx_clc_lesson (lesson_id),
    INDEX idx_clc_parent (parent_id)
);
