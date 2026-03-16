ALTER TABLE courses
    ADD COLUMN is_external TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN external_token VARCHAR(64) NULL,
    ADD UNIQUE KEY uq_courses_external_token (external_token);

CREATE TABLE course_partner_branding (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_name VARCHAR(255) NULL,
    logo_url VARCHAR(255) NULL,
    primary_color VARCHAR(32) NULL,
    secondary_color VARCHAR(32) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_course_partner_branding_user_id (user_id)
);
