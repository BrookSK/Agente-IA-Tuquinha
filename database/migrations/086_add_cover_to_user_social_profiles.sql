ALTER TABLE user_social_profiles
    ADD COLUMN cover_path VARCHAR(255) NULL AFTER avatar_path,
    ADD COLUMN cover_updated_at DATETIME NULL AFTER cover_path;
