CREATE TABLE IF NOT EXISTS project_suggestion_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    conversation_id INT UNSIGNED NOT NULL,
    user_message TEXT NOT NULL,
    assistant_reply TEXT NOT NULL,
    status ENUM('pending', 'running', 'done', 'error') NOT NULL DEFAULT 'pending',
    error_text TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    done_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adiciona colunas de projeto na tabela de sugestões de IA (se existir)
CREATE TABLE IF NOT EXISTS ai_prompt_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NULL,
    category VARCHAR(100) DEFAULT NULL,
    suggestion TEXT NOT NULL,
    rationale TEXT DEFAULT NULL,
    source_conversation_id INT UNSIGNED NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_project_status (project_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
