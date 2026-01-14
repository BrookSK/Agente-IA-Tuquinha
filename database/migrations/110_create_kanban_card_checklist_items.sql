CREATE TABLE kanban_card_checklist_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  card_id INT UNSIGNED NOT NULL,
  content VARCHAR(500) NOT NULL,
  is_done TINYINT(1) NOT NULL DEFAULT 0,
  position INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_kb_checklist_card_id (card_id),
  INDEX idx_kb_checklist_card_pos (card_id, position),
  CONSTRAINT fk_kb_checklist_card_id FOREIGN KEY (card_id) REFERENCES kanban_cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
