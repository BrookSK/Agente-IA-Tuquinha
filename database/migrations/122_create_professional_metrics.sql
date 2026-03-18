CREATE TABLE IF NOT EXISTS professional_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    total_students INT NOT NULL DEFAULT 0,
    total_revenue_cents INT NOT NULL DEFAULT 0,
    active_courses INT NOT NULL DEFAULT 0,
    total_sales INT NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_partner (partner_id),
    INDEX idx_partner (partner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
