-- Tabela para armazenar menções de aulas em comentários de comunidades
CREATE TABLE IF NOT EXISTS community_lesson_mentions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    comment_id INT NULL,
    lesson_id INT NOT NULL,
    mentioned_by_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_topic_id (topic_id),
    INDEX idx_lesson_id (lesson_id),
    INDEX idx_mentioned_by (mentioned_by_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
