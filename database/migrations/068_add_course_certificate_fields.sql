ALTER TABLE courses
    ADD COLUMN certificate_syllabus TEXT NULL AFTER badge_image_path,
    ADD COLUMN certificate_workload_hours INT NULL AFTER certificate_syllabus,
    ADD COLUMN certificate_location VARCHAR(255) NULL AFTER certificate_workload_hours;

ALTER TABLE user_course_badges
    ADD COLUMN certificate_code VARCHAR(64) NULL AFTER rating,
    ADD COLUMN started_at DATE NULL AFTER certificate_code,
    ADD COLUMN finished_at DATE NULL AFTER started_at,
    ADD COLUMN certificate_issued_at DATETIME NULL AFTER finished_at,
    ADD UNIQUE KEY uniq_ucb_certificate_code (certificate_code);
