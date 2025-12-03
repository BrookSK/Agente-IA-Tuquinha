ALTER TABLE users
    ADD COLUMN token_balance INT NOT NULL DEFAULT 0 AFTER billing_state,
    ADD COLUMN token_spent_total INT NOT NULL DEFAULT 0 AFTER token_balance,
    ADD COLUMN last_token_reset_at DATETIME NULL AFTER token_spent_total;
