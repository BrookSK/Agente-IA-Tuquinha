ALTER TABLE project_file_versions
ADD COLUMN openai_file_id VARCHAR(255) NULL AFTER storage_url;
