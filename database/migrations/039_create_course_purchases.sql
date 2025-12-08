CREATE TABLE IF NOT EXISTS course_purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    amount_cents INT NOT NULL,
    billing_type VARCHAR(20) NOT NULL,
    asaas_payment_id VARCHAR(100) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    paid_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_course_purchases_user (user_id),
    INDEX idx_course_purchases_course (course_id),
    INDEX idx_course_purchases_payment (asaas_payment_id),
    INDEX idx_course_purchases_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
