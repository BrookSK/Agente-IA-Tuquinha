CREATE TABLE kanban_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    position INT NOT NULL DEFAULT 0,
    due_date DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kanban_cards_list_id (list_id),
    INDEX idx_kanban_cards_list_position (list_id, position),
    CONSTRAINT fk_kanban_cards_list_id FOREIGN KEY (list_id) REFERENCES kanban_lists(id) ON DELETE CASCADE
);
