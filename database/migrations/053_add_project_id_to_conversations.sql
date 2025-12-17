ALTER TABLE conversations
    ADD COLUMN project_id INT NULL AFTER user_id;

ALTER TABLE conversations
    ADD INDEX idx_conversations_project_id (project_id);
