ALTER TABLE communities
    ADD COLUMN allow_poll_closing TINYINT(1) NOT NULL DEFAULT 0 AFTER forum_type;
