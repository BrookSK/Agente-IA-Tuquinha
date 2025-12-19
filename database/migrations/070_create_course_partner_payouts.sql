CREATE TABLE IF NOT EXISTS course_partner_payouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    period_year INT NOT NULL,
    period_month INT NOT NULL,
    amount_cents INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'paid',
    paid_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_partner_period (partner_id, period_year, period_month),
    INDEX idx_partner (partner_id),
    INDEX idx_period (period_year, period_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
