-- Adiciona campo tagline aos cursos para frase personalizável
ALTER TABLE courses
ADD COLUMN tagline VARCHAR(255) DEFAULT 'Aprenda Agora.' AFTER description;
