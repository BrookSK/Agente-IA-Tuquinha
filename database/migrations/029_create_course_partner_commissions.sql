CREATE TABLE course_partner_commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    course_id INT NOT NULL,
    commission_percent DECIMAL(5,2) NOT NULL,
    UNIQUE KEY uniq_partner_course (partner_id, course_id)
);
