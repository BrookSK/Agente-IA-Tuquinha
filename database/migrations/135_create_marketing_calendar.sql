-- Adiciona permissões de Agenda de Marketing aos planos
ALTER TABLE plans
    ADD COLUMN allow_marketing_calendar TINYINT(1) NOT NULL DEFAULT 0 AFTER allow_kanban_sharing;

ALTER TABLE plans
    ADD COLUMN allow_marketing_calendar_sharing TINYINT(1) NOT NULL DEFAULT 0 AFTER allow_marketing_calendar;

-- Tabela de eventos da agenda de marketing
CREATE TABLE IF NOT EXISTS marketing_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL DEFAULT '',
    event_date DATE NOT NULL,
    event_type ENUM('post','story','reels','video','email','anuncio','outro') NOT NULL DEFAULT 'post',
    status ENUM('planejado','produzido','postado') NOT NULL DEFAULT 'planejado',
    responsible VARCHAR(255) DEFAULT NULL,
    color VARCHAR(20) NOT NULL DEFAULT '#e53935',
    notes TEXT DEFAULT NULL,
    reference_links JSON DEFAULT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    public_token VARCHAR(64) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_owner_date (owner_user_id, event_date),
    INDEX idx_public_token (public_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de compartilhamento privado da agenda
CREATE TABLE IF NOT EXISTS marketing_event_shares (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_user_id INT UNSIGNED NOT NULL,
    shared_with_user_id INT UNSIGNED NOT NULL,
    role ENUM('view','edit') NOT NULL DEFAULT 'view',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_owner_shared (owner_user_id, shared_with_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
