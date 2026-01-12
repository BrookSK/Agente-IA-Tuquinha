CREATE TABLE kanban_lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    position INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kanban_lists_board_id (board_id),
    INDEX idx_kanban_lists_board_position (board_id, position),
    CONSTRAINT fk_kanban_lists_board_id FOREIGN KEY (board_id) REFERENCES kanban_boards(id) ON DELETE CASCADE
);
