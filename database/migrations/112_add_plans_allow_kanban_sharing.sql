ALTER TABLE plans
    ADD COLUMN allow_kanban_sharing TINYINT(1) NOT NULL DEFAULT 0;
