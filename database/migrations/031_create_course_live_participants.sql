CREATE TABLE course_live_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    live_id INT NOT NULL,
    user_id INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reminder_sent_at DATETIME NULL,
    UNIQUE KEY uniq_live_user (live_id, user_id),
    INDEX idx_course_live_participants_live (live_id)
);
