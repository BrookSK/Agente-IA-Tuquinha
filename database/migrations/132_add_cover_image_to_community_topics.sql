ALTER TABLE community_topics
    ADD COLUMN cover_image_url VARCHAR(512) NULL AFTER body,
    ADD COLUMN cover_image_mime VARCHAR(120) NULL AFTER cover_image_url;
