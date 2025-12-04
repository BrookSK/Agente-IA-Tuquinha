ALTER TABLE course_lives
    ADD COLUMN recording_link VARCHAR(500) NULL AFTER meet_link,
    ADD COLUMN recording_published_at DATETIME NULL AFTER recording_link;
