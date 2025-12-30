ALTER TABLE attachments
    ADD COLUMN openai_file_id VARCHAR(100) NULL AFTER size;

ALTER TABLE attachments
    ADD INDEX idx_attachments_openai_file_id (openai_file_id);
