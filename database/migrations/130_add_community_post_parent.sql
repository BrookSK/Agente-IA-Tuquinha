-- Adiciona coluna parent_post_id para permitir respostas a comentários específicos
ALTER TABLE community_topic_posts 
ADD COLUMN parent_post_id INT NULL AFTER topic_id,
ADD INDEX idx_parent_post (parent_post_id);
