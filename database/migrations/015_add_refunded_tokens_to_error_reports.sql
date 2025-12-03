ALTER TABLE error_reports
    ADD COLUMN refunded_tokens INT NOT NULL DEFAULT 0 AFTER tokens_used;
