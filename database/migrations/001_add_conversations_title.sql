ALTER TABLE conversations
    ADD COLUMN title VARCHAR(255) NULL AFTER session_id;
