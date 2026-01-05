INSERT INTO settings (`key`, `value`)
SELECT 'tuquinha_about_video_url', ''
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM settings WHERE `key` = 'tuquinha_about_video_url'
);
