ALTER TABLE users 
ADD COLUMN is_external_course_user TINYINT(1) NOT NULL DEFAULT 0 AFTER is_admin,
ADD COLUMN external_course_partner_id INT NULL AFTER is_external_course_user,
ADD INDEX idx_external_partner (external_course_partner_id);
