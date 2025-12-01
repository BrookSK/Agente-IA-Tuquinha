CREATE TABLE conversations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    role ENUM('user', 'assistant') NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_conversation FOREIGN KEY (conversation_id)
        REFERENCES conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE asaas_configs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    environment ENUM('sandbox', 'production') NOT NULL DEFAULT 'sandbox',
    sandbox_api_key VARCHAR(255) NOT NULL,
    production_api_key VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    price_cents INT UNSIGNED NOT NULL,
    monthly_message_limit INT UNSIGNED,
    benefits TEXT,
    asaas_plan_id VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    allowed_models TEXT NULL,
    default_model VARCHAR(100) NULL,
    allow_audio TINYINT(1) NOT NULL DEFAULT 0,
    allow_images TINYINT(1) NOT NULL DEFAULT 0,
    allow_files TINYINT(1) NOT NULL DEFAULT 0,
    max_file_size_bytes INT UNSIGNED NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Planos padrão
INSERT INTO plans (
    name, slug, description, price_cents, monthly_message_limit, benefits,
    asaas_plan_id, is_active, sort_order,
    allowed_models, default_model,
    allow_audio, allow_images, allow_files, max_file_size_bytes
) VALUES
(
    'Free', 'free', 'Plano gratuito para testar o fluxo com o Tuquinha.',
    0, 100,
    'Acesso básico ao chat do Tuquinha\nModelo padrão econômico\nSem necessidade de cartão para começar',
    NULL, 1, 1,
    '["gpt-4o-mini"]', 'gpt-4o-mini',
    1, 1, 1, 5242880
),
(
    'Tuquinha Pro', 'pro-30', 'Plano mensal para quem usa o Tuquinha no dia a dia.',
    3000, 1000,
    'Mais mensagens por mês\nPrioridade no uso do modelo padrão\nEnvio de áudios, imagens e arquivos para análise',
    NULL, 1, 2,
    '["gpt-4o-mini","gpt-4o"]', 'gpt-4o',
    1, 1, 1, 10485760
),
(
    'Tuquinha Expert', 'pro-60', 'Para quem vive de branding e quer o máximo do Tuquinha.',
    6000, 3000,
    'Mais mensagens e prioridade máxima\nAcesso ampliado a modelos\nSuporte a anexos maiores',
    NULL, 1, 3,
    '["gpt-4o-mini","gpt-4o","gpt-4.1"]', 'gpt-4.1',
    1, 1, 1, 20971520
);

CREATE TABLE subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id INT UNSIGNED NOT NULL,
    customer_name VARCHAR(150) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_cpf VARCHAR(20) NOT NULL,
    customer_phone VARCHAR(30) DEFAULT NULL,
    customer_postal_code VARCHAR(20) DEFAULT NULL,
    customer_address VARCHAR(200) DEFAULT NULL,
    customer_address_number VARCHAR(20) DEFAULT NULL,
    customer_complement VARCHAR(100) DEFAULT NULL,
    customer_province VARCHAR(100) DEFAULT NULL,
    customer_city VARCHAR(100) DEFAULT NULL,
    customer_state VARCHAR(2) DEFAULT NULL,
    asaas_customer_id VARCHAR(100) DEFAULT NULL,
    asaas_subscription_id VARCHAR(100) DEFAULT NULL,
    status ENUM('pending', 'active', 'canceled', 'expired', 'error') NOT NULL DEFAULT 'pending',
    started_at DATETIME DEFAULT NULL,
    canceled_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (name, email, password_hash, is_admin)
VALUES ('Lucas Vacari', 'lucas@lrvweb.com.br', '$2y$10$3YAHki.1HX7vSHh3OaO1JuV1KUdrNfmIkseijCKhn05yCQPP/shIu', 1);

CREATE TABLE attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    message_id INT UNSIGNED DEFAULT NULL,
    type ENUM('image', 'file', 'audio') NOT NULL,
    path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attachments_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
