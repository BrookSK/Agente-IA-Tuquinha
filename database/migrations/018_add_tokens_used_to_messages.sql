ALTER TABLE messages
    ADD COLUMN tokens_used INT NULL AFTER content;
