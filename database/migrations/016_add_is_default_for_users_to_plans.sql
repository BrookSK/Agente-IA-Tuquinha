ALTER TABLE plans
    ADD COLUMN is_default_for_users TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;

-- Garante que algum plano atual possa ser usado como padrão, se desejar.
-- Por padrão, todos ficam como 0 (nenhum padrão definido).
