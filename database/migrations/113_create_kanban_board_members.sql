CREATE TABLE IF NOT EXISTS kanban_board_members (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    board_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'member',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_board_user (board_id, user_id),
    KEY idx_board_id (board_id),
    KEY idx_user_id (user_id),
    CONSTRAINT fk_kbm_board FOREIGN KEY (board_id) REFERENCES kanban_boards(id) ON DELETE CASCADE,
    CONSTRAINT fk_kbm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
