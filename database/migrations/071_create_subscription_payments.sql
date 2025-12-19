CREATE TABLE IF NOT EXISTS subscription_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount_cents INT NOT NULL,
    asaas_payment_id VARCHAR(100) DEFAULT NULL,
    billing_type VARCHAR(20) DEFAULT NULL,
    paid_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_subscription_payment (subscription_id, asaas_payment_id),
    INDEX idx_subscription_paid_at (subscription_id, paid_at),
    INDEX idx_plan_paid_at (plan_id, paid_at),
    INDEX idx_paid_at (paid_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
