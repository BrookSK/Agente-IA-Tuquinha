ALTER TABLE plans
    ADD COLUMN referral_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER allow_courses,
    ADD COLUMN referral_min_active_days INT NULL AFTER referral_enabled,
    ADD COLUMN referral_referrer_tokens INT NULL AFTER referral_min_active_days,
    ADD COLUMN referral_friend_tokens INT NULL AFTER referral_referrer_tokens,
    ADD COLUMN referral_free_days INT NULL AFTER referral_friend_tokens,
    ADD COLUMN referral_require_card TINYINT(1) NOT NULL DEFAULT 1 AFTER referral_free_days;

ALTER TABLE users
    ADD COLUMN referral_code VARCHAR(50) NULL UNIQUE AFTER token_spent_total;

CREATE TABLE IF NOT EXISTS user_referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_user_id INT NOT NULL,
    referred_user_id INT NULL,
    referred_email VARCHAR(255) NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('pending','completed','canceled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uniq_plan_email (plan_id, referred_email),
    INDEX idx_referrer (referrer_user_id),
    INDEX idx_referred (referred_user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
