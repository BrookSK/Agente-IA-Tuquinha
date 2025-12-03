ALTER TABLE plans
    ADD COLUMN monthly_token_limit INT NULL AFTER monthly_message_limit,
    ADD COLUMN extra_token_price_per_1k DECIMAL(10,4) NULL AFTER monthly_token_limit;
