CREATE TABLE IF NOT EXISTS community_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_cc_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO community_categories (name, sort_order, is_active) VALUES
    ('Branding & Design', 10, 1),
    ('Estudos & Concursos', 20, 1),
    ('Carreira & Networking', 30, 1),
    ('Tecnologia & Programação', 40, 1),
    ('Marketing & Vendas', 50, 1),
    ('Conteúdo & Social', 60, 1),
    ('Finanças & Negócios', 70, 1),
    ('Saúde & Bem-estar', 80, 1),
    ('Cursos', 90, 1),
    ('Off-topic & Resenha', 100, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_active = VALUES(is_active);
