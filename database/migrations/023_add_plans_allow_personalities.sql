ALTER TABLE plans
    ADD COLUMN allow_personalities TINYINT(1) NOT NULL DEFAULT 1 AFTER allow_files;
