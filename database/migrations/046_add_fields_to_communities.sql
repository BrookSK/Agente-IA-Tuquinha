CREATE TABLE IF NOT EXISTS tmp_ensure_migrations_directory_intact (
    id INT PRIMARY KEY AUTO_INCREMENT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE tmp_ensure_migrations_directory_intact;

ALTER TABLE communities
    ADD COLUMN language VARCHAR(20) NULL AFTER description,
    ADD COLUMN category VARCHAR(100) NULL AFTER language,
    ADD COLUMN cover_image_path VARCHAR(255) NULL AFTER image_path,
    ADD COLUMN community_type VARCHAR(20) NOT NULL DEFAULT 'public' AFTER category,
    ADD COLUMN posting_policy VARCHAR(30) NOT NULL DEFAULT 'any_member' AFTER community_type,
    ADD COLUMN forum_type VARCHAR(50) NULL AFTER posting_policy;

ALTER TABLE community_members
    ADD COLUMN is_blocked TINYINT(1) NOT NULL DEFAULT 0 AFTER left_at,
    ADD COLUMN blocked_reason VARCHAR(255) NULL AFTER is_blocked;
