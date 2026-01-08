ALTER TABLE community_topics
    ADD COLUMN media_url VARCHAR(512) NULL AFTER body,
    ADD COLUMN media_mime VARCHAR(120) NULL AFTER media_url,
    ADD COLUMN media_kind VARCHAR(20) NULL AFTER media_mime;

ALTER TABLE community_topic_posts
    ADD COLUMN media_url VARCHAR(512) NULL AFTER body,
    ADD COLUMN media_mime VARCHAR(120) NULL AFTER media_url,
    ADD COLUMN media_kind VARCHAR(20) NULL AFTER media_mime;
