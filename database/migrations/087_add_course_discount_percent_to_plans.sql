ALTER TABLE plans
    ADD COLUMN course_discount_percent DECIMAL(5,2) NULL AFTER allow_courses;
