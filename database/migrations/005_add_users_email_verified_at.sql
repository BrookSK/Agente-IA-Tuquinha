ALTER TABLE users
    ADD COLUMN email_verified_at DATETIME NULL AFTER is_active;
