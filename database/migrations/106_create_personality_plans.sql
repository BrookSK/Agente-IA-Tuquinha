CREATE TABLE personality_plans (
    personality_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (personality_id, plan_id),
    INDEX idx_personality_plans_plan_id (plan_id),
    CONSTRAINT fk_personality_plans_personality_id FOREIGN KEY (personality_id) REFERENCES personalities(id) ON DELETE CASCADE,
    CONSTRAINT fk_personality_plans_plan_id FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
