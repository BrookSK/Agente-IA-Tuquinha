ALTER TABLE users
ADD COLUMN preferred_name VARCHAR(190) NULL AFTER name,
ADD COLUMN global_memory TEXT NULL AFTER preferred_name,
ADD COLUMN global_instructions TEXT NULL AFTER global_memory;
