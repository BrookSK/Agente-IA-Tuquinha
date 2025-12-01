ALTER TABLE plans
    ADD COLUMN history_retention_days INT UNSIGNED NULL AFTER max_file_size_bytes;
