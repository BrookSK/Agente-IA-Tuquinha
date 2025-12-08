ALTER TABLE course_lesson_comments
    ADD COLUMN live_id INT NULL AFTER lesson_id,
    MODIFY lesson_id INT NULL,
    ADD INDEX idx_clc_live (live_id);
