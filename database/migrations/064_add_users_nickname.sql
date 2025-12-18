ALTER TABLE users
    ADD COLUMN nickname VARCHAR(32) NULL AFTER preferred_name,
    ADD UNIQUE KEY uniq_users_nickname (nickname);
