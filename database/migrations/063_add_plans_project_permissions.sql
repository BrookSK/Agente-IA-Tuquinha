ALTER TABLE plans
    ADD COLUMN allow_projects_access TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN allow_projects_create TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN allow_projects_edit TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN allow_projects_share TINYINT(1) NOT NULL DEFAULT 0;
